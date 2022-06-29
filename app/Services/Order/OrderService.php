<?php

namespace App\Services\Order;

use App\Services\BaseService;
use App\Services\SystemService;

class OrderService extends BaseService
{
    public function getFreight($price)
    {
        $freightPrice = 0;
        $freightMin = SystemService::getInstance()->getFreightMin();
        if (bccomp($freightMin, $price) == 1) {
            $freightPrice = SystemService::getInstance()->getFreightValue();
        }
        return $freightPrice;
    }
}
