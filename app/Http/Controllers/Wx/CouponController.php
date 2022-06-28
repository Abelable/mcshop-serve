<?php

namespace App\Http\Controllers\Wx;

use App\Input\PageInput;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponUser;
use App\Services\Promotion\CouponService;

class CouponController extends WxController
{
    protected $except = ['list'];

    public function list()
    {
        $input = PageInput::new();
        $columns = ['id', 'name', 'desc', 'tag', 'discount', 'min', 'days', 'start_time', 'end_time'];
        $list = CouponService::getInstance()->list($input, $columns);
        return $this->successPaginate($list);
    }

    public function mylist()
    {
        $status = $this->verifyInteger('status');
        $input = PageInput::new();
        $paginate = CouponService::getInstance()->mylist($this->userId(), $status, $input);
        $list = collect($paginate->items());

        $couponIds = $list->pluck('coupon_id')->toArray();
        $couponList = CouponService::getInstance()->getCoupons($couponIds)->keyBy('id');

        $myCouponList = $list->map(function (CouponUser $item) use ($couponList) {
            /** @var Coupon $coupon */
            $coupon = $couponList->get($item->coupon_id);
            return [
                'id' => $item->id,
                'cid' => $coupon->id,
                'name' => $coupon->name,
                'desc' => $coupon->desc,
                'tag' => $coupon->tag,
                'min' => $coupon->min,
                'discount' => $coupon->discount,
                'startTime' => $item->start_time,
                'endTime' => $item->end_time,
                'available' => false
            ];
        });

        return $this->success($this->paginate($paginate, $myCouponList));
    }

    public function receive()
    {
        $couponId = $this->verifyRequiredId('couponId');
        CouponService::getInstance()->receive($couponId, $this->userId());
        return $this->success();
    }
}
