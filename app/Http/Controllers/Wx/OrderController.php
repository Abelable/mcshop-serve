<?php

namespace App\Http\Controllers\Wx;

use App\Models\Order\Order;
use App\Services\Order\OrderService;
use App\Utils\CodeResponse;
use App\Utils\Inputs\OrderSubmitInput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yansongda\LaravelPay\Facades\Pay;

class OrderController extends WxController
{
    public function submit()
    {
        /** @var OrderSubmitInput $input */
        $input = OrderSubmitInput::new();

        // 分布式锁，防止重复请求
        $lockKey = sprintf('order_submit_%s_%s', $this->userId(), md5(serialize($input))); // md5(serialize($input)): 序列化请求参数
        $lock = Cache::lock($lockKey, 5);
        if (!$lock->get()) {
            $this->fail(CodeResponse::FAIL, '请勿重复请求');
        }

        /** @var Order $order */
        $order = DB::transaction(function () use ($input) {
            return OrderService::getInstance()->submit($this->userId(), $input);
        });

        return $this->success([
           'orderId' => $order->id,
            'grouponLinkId' => $input->grouponLinkId
        ]);
    }

    public function cancel()
    {
        $orderId = $this->verifyRequiredId('orderId');
        OrderService::getInstance()->userCancel($this->userId(), $orderId);
        return $this->success();
    }

    public function confirm()
    {
        $orderId = $this->verifyRequiredId('orderId');
        OrderService::getInstance()->confirm($this->userId(), $orderId);
        return $this->success();
    }

    public function refund()
    {
        $orderId = $this->verifyRequiredId('orderId');
        OrderService::getInstance()->refund($this->userId(), $orderId);
        return $this->success();
    }

    public function delete()
    {
        $orderId = $this->verifyRequiredId('orderId');
        OrderService::getInstance()->delete($this->userId(), $orderId);
        return $this->success();
    }

    public function detail()
    {
        $orderId = $this->verifyId('orderId');
        $detail = OrderService::getInstance()->detail($this->userId(), $orderId);
        return $this->success($detail);
    }

    public function h5pay()
    {
        $orderId = $this->verifyRequiredId('orderId');
        $order = OrderService::getInstance()->getWxPayOrder($this->userId(), $orderId);
        return Pay::wechat()->wap($order);
    }

    public function h5alipay()
    {
        $orderId = $this->verifyRequiredId('orderId');
        $order = OrderService::getInstance()->getAliPayOrder($this->userId(), $orderId);
        return Pay::alipay()->wap($order);
    }

    public function wxNotify()
    {
        $data = Pay::wechat()->verify()->toArray();
        Log::info('wxNotify', $data);
        DB::transaction(function () use ($data) {
            OrderService::getInstance()->wxNotify($data);
        });
        return Pay::wechat()->success();
    }

    public function alipayNotify()
    {
        $data = Pay::alipay()->verify()->toArray();
        Log::info('alipayNotify', $data);
        DB::transaction(function () use ($data) {
            OrderService::getInstance()->alipayNotify($data);
        });
        return Pay::alipay()->success();
    }
}
