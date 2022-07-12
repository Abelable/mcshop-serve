<?php

namespace App\Http\Controllers\Wx;

use App\Models\Order\Order;
use App\Services\Order\OrderService;
use App\Utils\Enums\OrderEnums;

class UserController extends WxController
{
    public function index()
    {
        $unpaid = 0;
        $unship = 0;
        $unrecv = 0;
        $uncomment = 0;

        $orderList = OrderService::getInstance()->getOrderListByUserId($this->userId());
        $orderList->map(function (Order $order) use ($unpaid, $unship, $unrecv, $uncomment) {
            switch ($order->order_status) {
                case OrderEnums::STATUS_CREATE:
                    $unpaid++;
                    break;
                case OrderEnums::STATUS_PAY:
                    $unship++;
                    break;
                case OrderEnums::STATUS_SHIP:
                    $unrecv++;
                    break;
                case OrderEnums::STATUS_CONFIRM:
                    $uncomment++;
                    break;
            }
        });
        return $this->success([
            'order' => compact('unpaid', 'unship', 'unrecv', 'uncomment')
        ]);
    }
}
