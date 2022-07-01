<?php

namespace App\Http\Controllers\Wx;

use App\Models\Order\Order;
use App\Services\Order\OrderService;
use App\Utils\CodeResponse;
use App\Utils\Inputs\OrderSubmitInput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
}
