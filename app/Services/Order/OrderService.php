<?php

namespace App\Services\Order;

use App\Exceptions\BusinessException;
use App\Jobs\OverTimeCancelOrder;
use App\Models\Goods\Goods;
use App\Models\Goods\GoodsProduct;
use App\Models\Order\Cart;
use App\Models\Order\Order;
use App\Models\Order\OrderGoods;
use App\Notifications\NewPaidOrderEmailNotify;
use App\Notifications\NewPaidOrderSmsNotify;
use App\Services\BaseService;
use App\Services\Goods\GoodsService;
use App\Services\Promotion\CouponService;
use App\Services\Promotion\GrouponService;
use App\Services\SystemService;
use App\Services\User\AddressService;
use App\Services\User\UserService;
use App\Utils\CodeResponse;
use App\Utils\Enums\OrderEnums;
use App\Utils\Inputs\OrderSubmitInput;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OrderService extends BaseService
{
    public function submit(int $userId, OrderSubmitInput $input)
    {
        // 1. 验证团购规则有效性
        if (!empty($input->grouponRulesId)) {
            GrouponService::getInstance()->checkGrouponRuleValid($userId, $input->grouponRulesId, $input->grouponLinkId);
        }

        // 2. 获取地址
        $address = AddressService::getInstance()->getAddress($userId, $input->addressId);
        if (is_null($address)) {
            $this->throwBadArgumentValue();
        }

        // 3. 获取购物车商品列表
        $cartList = CartService::getInstance()->getPreorderCartList($userId, $input->cartId);

        // 4. 计算团购优惠和商品价格
        $grouponPrice = 0;
        $goodsTotalPrice = CartService::getInstance()->getCartPriceCutGroupon($cartList, $input->grouponRulesId, $grouponPrice);

        // 5. 获取优惠券折扣
        $couponPrice = 0;
        if ($input->couponId > 0) {
            $coupon = CouponService::getInstance()->getCoupon($input->couponId);
            $couponUser = CouponService::getInstance()->getCouponUser($input->userCouponId);
            $isUsable = CouponService::getInstance()->checkCouponUsable($coupon, $couponUser, $goodsTotalPrice);
            if ($isUsable) {
                $couponPrice = $coupon->discount;
            }
        }

        // 6. 获取运费
        $freightPrice = SystemService::getInstance()->getFreight($goodsTotalPrice);

        // 7. 计算订单价格
        $orderPrice = bcadd($goodsTotalPrice, $freightPrice, 2);
        $orderPrice = bcsub($orderPrice, $couponPrice, 2);
        $orderPrice = max(0, $orderPrice);

        // 8. 保存订单
        $order = Order::new();
        $order->user_id = $userId;
        $order->order_sn = $this->generateOrderSn();
        $order->order_status = OrderEnums::STATUS_CREATE;
        $order->consignee = $address->name;
        $order->mobile = $address->tel;
        $order->address = $address->province . $address->city . $address->county . ' ' . $address->address_detail;
        $order->message = $input->message;
        $order->goods_price = $goodsTotalPrice;
        $order->freight_price = $freightPrice;
        $order->integral_price = 0;
        $order->coupon_price = $couponPrice;
        $order->groupon_price = $grouponPrice;
        $order->order_price = $orderPrice;
        $order->actual_price = $orderPrice;
        $order->save();

        // 9. 生成订单商品快照
        $this->saveOrderGoodsList($cartList, $order->id);

        // 10. 清空购物车
        CartService::getInstance()->clearCart($userId, $input->cartId);

        // 11. 商品减库存
        $this->reduceProductsStock($cartList);

        // 12. 添加团购记录
        GrouponService::getInstance()->openOrJoinGroupon($userId, $order->id, $input->grouponRulesId, $input->grouponLinkId);

        // 13. 设置订单支付超时取消订单任务
        dispatch(new OverTimeCancelOrder($userId, $order->id));

        return $order;
    }

    public function generateOrderSn()
    {
        return retry(5, function () {
            $orderSn = date('YmdHis') . Str::random(6);
            if ($this->isOrderSnExists($orderSn)) {
                \Log::warning('订单号生成失败，orderSn: ' . $orderSn);
                $this->throwBusinessException(CodeResponse::FAIL, '订单号生成失败');
            }
            return $orderSn;
        });
    }

    public function isOrderSnExists(string $orderSn)
    {
        return Order::query()->where('order_sn', $orderSn)->exists();
    }

    public function saveOrderGoodsList($cartList, $orderId)
    {
        /** @var Cart $cart */
        foreach ($cartList as $cart) {
            $orderGoods = OrderGoods::new();
            $orderGoods->order_id = $orderId;
            $orderGoods->goods_id = $cart->goods_id;
            $orderGoods->goods_sn = $cart->goods_sn;
            $orderGoods->product_id = $cart->product_id;
            $orderGoods->goods_name = $cart->goods_name;
            $orderGoods->price = $cart->price;
            $orderGoods->number = $cart->number;
            $orderGoods->specifications = $cart->specifications;
            $orderGoods->save();
        }
    }

    /**
     * @param Cart[]|Collection $cartList
     * @return void
     * @throws BusinessException
     */
    public function reduceProductsStock($cartList)
    {
        $productIds = $cartList->pluck('product_id')->toArray();
        $productList = GoodsService::getInstance()->getProductListByIds($productIds)->keyBy('id');

        /** @var Cart $cart */
        foreach ($cartList as $cart) {
            /** @var GoodsProduct $product */
            $product = $productList->get($cart->product_id);
            if (is_null($product)) {
                $this->throwBadArgumentValue();
            }
            if ($product->number < $cart->number) {
                $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
            }
            $rows = GoodsService::getInstance()->reduceStock($product->id, $cart->number);
            if ($rows == 0) {
                $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
            }
        }
    }

    public function userCancel(int $userId, int $orderId)
    {
        DB::transaction(function () use ($userId, $orderId) {
            return $this->cancel($userId, $orderId);
        });
    }

    public function systemCancel(int $userId, int $orderId)
    {
        DB::transaction(function () use ($userId, $orderId) {
            return $this->cancel($userId, $orderId, 'system');
        });
    }

    public function cancel(int $userId, int $orderId, $role = 'user')
    {
        $order = $this->getOrder($userId, $orderId);
        if (is_null($order)) {
            $this->throwBadArgumentValue();
        }
        if (!$order->canCancelHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '订单不能取消');
        }

        switch ($role) {
            case 'system':
                $order->order_status = OrderEnums::STATUS_AUTO_CANCEL;
                break;
            case 'admin':
                $order->order_status = OrderEnums::STATUS_ADMIN_CANCEL;
                break;
            case 'user':
                $order->order_status = OrderEnums::STATUS_CANCEL;
                break;
        }

        if ($order->cas() == 0) {
            $this->throwUpdateFail();
        }

        // 返还库存
        $this->returnStock($order->id);

        return $order;
    }

    public function refund(int $userId, int $orderId)
    {
        $order = $this->getOrder($userId, $orderId);
        if (is_null($order)) {
            $this->throwBadArgumentValue();
        }
        if (!$order->canRefundHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能申请退款');
        }

        $order->order_status = OrderEnums::STATUS_REFUND;

        if ($order->cas() == 0) {
            $this->throwUpdateFail();
        }

        return $order;
    }

    public function confirm(int $userId, int $orderId, $isAuto = false)
    {
        $order = $this->getOrder($userId, $orderId);
        if (is_null($order)) {
            $this->throwBadArgumentValue();
        }
        if (!$order->canConfirmHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能被确认收货');
        }

        $order->comments = $this->countOrderGoods($orderId);
        $order->order_status = $isAuto ? OrderEnums::STATUS_AUTO_CONFIRM : OrderEnums::STATUS_CONFIRM;
        $order->confirm_time = now()->toDateTimeString();

        if ($order->cas() == 0) {
            $this->throwUpdateFail();
        }

        return $order;
    }

    public function delete(int $userId, int $orderId)
    {
        $order = $this->getOrder($userId, $orderId);
        if (is_null($order)) {
            $this->throwBadArgumentValue();
        }
        if (!$order->canDeleteHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能删除');
        }
        $order->delete();

        // todo 售后
    }


    public function detail(int $userId, int $orderId)
    {
        $order = $this->getOrder($userId, $orderId);
        if (is_null($order)) {
            $this->throwBadArgumentValue();
        }

        $detail = Arr::only($order->toArray(), [
            'id',
            'orderSn',
            'message',
            'addTime',
            'consignee',
            'mobile',
            'address',
            'goodsPrice',
            'couponPrice',
            'freightPrice',
            'actualPrice',
            'aftersaleStatus'
        ]);

        $detail['orderStatusText'] = OrderEnums::STATUS_TEXT_MAP[$order->order_status];
        $detail['handleOption'] = $order->getCanHandleOptions();
        if ($order->isShipStatus()) {
            $detail['expCode'] = $order->ship_channel;
            $detail['expNo'] = $order->ship_sn;
            $detail['expName'] = ExpressService::getInstance()->getExpressName($order->ship_channel);
        }

        $goodsList = $this->getOrderGoodsList($orderId);
        $express = ExpressService::getInstance()->getOrderTraces($order->ship_channel, $order->order_sn);

        return [
            'orderInfo' => $detail,
            'orderGoods' => $goodsList,
            'expressInfo' => $express
        ];
    }

    public function getOrder(int $userId, int $orderId)
    {
        return Order::query()->where('user_id', $userId)->find($orderId);
    }

    public function getOrderByOrderSn(string $orderSn)
    {
        return Order::query()->where('order_sn', $orderSn)->first();
    }

    public function returnStock(int $orderId)
    {
        $goodsList = $this->getOrderGoodsList($orderId);

        /** @var Goods $goods */
        foreach ($goodsList as $goods) {
            $row = GoodsService::getInstance()->addStock($goods->product_id, $goods->number);
            if ($row == 0) {
                $this->throwUpdateFail();
            }
        }
    }

    public function getOrderGoodsList(int $orderId)
    {
        return OrderGoods::query()->where('order_id', $orderId)->get();
    }

    public function countOrderGoods(int $orderId)
    {
        return OrderGoods::query()->where('order_id', $orderId)->count('id');
    }

    public function getWxPayOrder(int $userId, int $orderId)
    {
        $order = $this->getPayOrderInfo($userId, $orderId);
        return [
            'out_trade_no' => $order->order_sn,
            'body' => '订单：' . $order->order_sn,
            'total_fee' => bcmul($order->actual_price, 100)
        ];
    }

    public function getAliPayOrder(int $userId, int $orderId)
    {
        $order = $this->getPayOrderInfo($userId, $orderId);
        return [
            'out_trade_no' => $order->order_sn,
            'total_amount' => $order->actual_price,
            'subject' => ''
        ];
    }

    public function getPayOrderInfo(int $userId, int $orderId)
    {
        $order = $this->getOrder($userId, $orderId);
        if (is_null($order)) {
            $this->throwBadArgumentValue();
        }
        if (!$order->canPayHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能支付');
        }
        return $order;
    }

    public function wxNotify(array $data)
    {
        $orderSn = $data['out_trade_no'] ?? '';
        $payId = $data['transaction_id'] ?? '';
        $price = bcdiv($data['total_price'], 100, 2);
        return $this->notify($price, $orderSn, $payId);
    }

    public function alipayNotify(array $data)
    {
        if (!in_array(($data['trade_status'] ?? ''), ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            $this->throwBusinessException(CodeResponse::ORDER_PAY_FAIL);
        }
        $orderSn = $data['out_trade_no'] ?? '';
        $payId = $data['transaction_id'] ?? '';
        $price = $data['total_amount'] ?? 0;
        return $this->notify($price,$orderSn, $payId);
    }

    public function notify($price, $orderSn, $payId)
    {
        $order = $this->getOrderByOrderSn($orderSn);
        if (is_null($order)) {
            $this->throwBadArgumentValue();
        }
        if ($order->isHadPaid()) {
            return $order;
        }
        if (bccomp($order->actual_price, $price, 2) != 0) {
            $errMsg = "支付回调，订单{$order->id}金额不一致，请检查，支付回调金额：{$price}，订单金额：{$order->actual_price}";
            \Log::error($errMsg);
            $this->throwBusinessException(CodeResponse::FAIL, $errMsg);
        }
        return $this->payOrder($order, $payId);
    }

    public function payOrder(Order $order, int $payId)
    {
        if (!$order->canPayHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_PAY_FAIL, '订单不能被支付');
        }

        $order->pay_id = $payId;
        $order->pay_time = now()->toDateTimeString();
        $order->order_status = OrderEnums::STATUS_PAY;
        if ($order->cas() == 0) {
            $this->throwUpdateFail();
        }

        // 处理团购订单
        GrouponService::getInstance()->payGrouponOrder($order->id);

        // 发送邮件给管理员
        Notification::route('mail', env('MAIL_USERNAME'))->notify(new NewPaidOrderEmailNotify($order->id));

        // 发送短信给用户
        $user = UserService::getInstance()->getUserById($order->user_id);
        $user->notify(new NewPaidOrderSmsNotify());

        return $order;
    }

    public function getOrderListByUserId(int $userId)
    {
        return Order::query()->where('user_id', $userId)->get();
    }
}
