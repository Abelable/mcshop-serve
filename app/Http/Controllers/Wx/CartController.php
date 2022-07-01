<?php

namespace App\Http\Controllers\Wx;

use App\Services\Order\CartService;
use App\Services\Order\OrderService;
use App\Services\Promotion\CouponService;
use App\Services\SystemService;
use App\Services\User\AddressService;

class CartController extends WxController
{
    public function index()
    {
        $cartList = CartService::getInstance()->getValidCartList($this->userId());
        $goodsCount = 0;
        $goodsAmount = 0;
        $checkedGoodsCount = 0;
        $checkedGoodsAmount = 0;

        foreach ($cartList as $cart) {
            $goodsCount += $cart->number;
            $amount = bcmul($cart->number, $cart->price, 2); // 精度乘法：bcmul
            $goodsAmount = bcadd($goodsAmount, $amount, 2); // 精度加法：bcadd
            if ($cart->checked) {
                $checkedGoodsCount += $cart->number;
                $checkedGoodsAmount = bcadd($checkedGoodsAmount, $amount, 2);
            }
        }

        return $this->success([
            'cartList' => $cartList,
            'cartTotal' => [
                'goodsCount' => $goodsCount,
                'goodsAmount' => (double)$goodsAmount, // 精度计算返回的值都是字符串，(double) 用于字符串转数值
                'checkedGoodsCount' => $checkedGoodsCount,
                'checkedGoodsAmount' => (double)$checkedGoodsAmount
            ]
        ]);
    }

    public function add()
    {
        $goodsId = $this->verifyRequiredId('goodsId');
        $productId = $this->verifyRequiredId('productId');
        $number = $this->verifyPositiveInteger('number', 0);

        CartService::getInstance()->add($this->userId(), $goodsId, $productId, $number);
        return $this->goodscount();
    }

    public function fastadd()
    {
        $goodsId = $this->verifyRequiredId('goodsId');
        $productId = $this->verifyRequiredId('productId');
        $number = $this->verifyPositiveInteger('number', 0);

        $cart = CartService::getInstance()->fastAdd($this->userId(), $goodsId, $productId, $number);
        return $this->success($cart->id);
    }

    public function goodscount()
    {
        $count = CartService::getInstance()->countCartProduct($this->userId());
        return $this->success($count);
    }

    public function update()
    {
        $id = $this->verifyRequiredId('id');
        $goodsId = $this->verifyRequiredId('goodsId');
        $productId = $this->verifyRequiredId('productId');
        $number = $this->verifyPositiveInteger('number', 0);

        $cart = CartService::getInstance()->getCart($id);
        if (is_null($cart)) {
            return $this->badArgumentValue();
        }

        if ($cart->goods_id != $goodsId || $cart->product_id != $productId) {
            return $this->badArgumentValue();
        }

        CartService::getInstance()->getGoodsInfo($goodsId, $productId, $number);

        $cart->number = $number;
        $ret = $cart->save();
        return $this->failOrSuccess($ret);
    }

    public function checked()
    {
        $productIds = $this->verifyArrayNotEmpty('productIds', []);
        $isChecked = $this->verifyBoolean('isChecked');
        CartService::getInstance()->updateChecked($this->userId(), $productIds, $isChecked);
        return $this->index();
    }

    public function delete()
    {
        $productIds = $this->verifyArrayNotEmpty('productIds', []);
        CartService::getInstance()->delete($this->userId(), $productIds);
        return $this->index();
    }

    public function checkout()
    {
        $addressId = $this->verifyInteger('addressId'); // 非必传
        $cartId = $this->verifyInteger('cartId'); // 非必传
        $grouponRulesId = $this->verifyInteger('grouponRulesId'); // 非必传
        $couponId = $this->verifyInteger('couponId'); // 非必传
        $userCouponId = $this->verifyInteger('userCouponId'); // 非必传

        // 获取收获地址
        $address = AddressService::getInstance()->getAddressOrDefault($this->userId(), $addressId);
        $addressId = $address->id ?? 0;

        // 获取购物车商品列表
        $cartList = CartService::getInstance()->getPreorderCartList($this->userId(), $cartId);

        // 计算团购优惠和商品价格
        $grouponPrice = 0;
        $goodsTotalPrice = CartService::getInstance()->getCartPriceCutGroupon($cartList, $grouponRulesId, $grouponPrice);

        // 获取当前订单可用优惠券信息：优惠券id、优惠券折扣、优惠券数量
        $usableCouponCount = 0;
        $couponUser = CouponService::getInstance()->getPreorderCouponUser($this->userId(), $couponId, $userCouponId, $goodsTotalPrice, $usableCouponCount);
        if (is_null($couponUser)) {
            $couponId = -1;
            $userCouponId = -1;
            $couponPrice = 0;
        } else {
            $couponId = $couponUser->coupon_id ?? 0;
            $userCouponId = $couponUser->id ?? 0;
            $couponPrice = CouponService::getInstance()->getCoupon($couponId)->discount ?? 0;
        }

        // 运费
        $freightPrice = SystemService::getInstance()->getFreight($goodsTotalPrice);

        // 订单总金额
        $orderPrice = bcadd($goodsTotalPrice, $freightPrice, 2);
        $orderPrice = bcsub($orderPrice, $couponPrice, 2);
        $orderPrice = max(0, $orderPrice);

        return $this->success([
            "addressId" => $addressId,
            "checkedAddress" => $address,
            "cartId" => $cartId,
            "checkedGoodsList" => $cartList,
            "grouponRulesId" => $grouponRulesId,
            "grouponPrice" => $grouponPrice,
            "goodsTotalPrice" => $goodsTotalPrice,
            "couponId" => $couponId,
            "userCouponId" => $userCouponId,
            "couponPrice" => $couponPrice,
            "availableCouponLength" => $usableCouponCount,
            "freightPrice" => $freightPrice,
            "orderTotalPrice" => $orderPrice,
            "actualPrice" => $orderPrice,
        ]);
    }
}
