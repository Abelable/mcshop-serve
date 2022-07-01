<?php

namespace App\Utils\Enums;

class OrderEnums
{
    const STATUS_CREATE          = 101;
    const STATUS_CANCEL          = 102;
    const STATUS_AUTO_CANCEL     = 103;
    const STATUS_ADMIN_CANCEL    = 104;
    const STATUS_PAY             = 201;
    const STATUS_REFUND          = 202;
    const STATUS_REFUND_CONFIRM  = 203;
    const STATUS_GROUPON_TIMEOUT = 204;
    const STATUS_SHIP            = 301;
    const STATUS_CONFIRM         = 401;
    const STATUS_AUTO_CONFIRM    = 402;
}