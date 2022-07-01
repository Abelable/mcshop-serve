<?php

namespace App\Utils\Enums;

class CouponEnums
{
    // 优惠券类型
    const TYPE_COMMON  = 0;

    // 优惠券商品限制
    const GOODS_TYPE_ALL = 0;

    // 优惠券状态
    const STATUS_NORMAL = 0;
    const STATUS_EXPIRED = 1;
    const STATUS_OUT = 2;

    // 优惠券时间类型
    const TIME_TYPE_DAYS = 0;
    const TIME_TYPE_TIME = 1;
}
