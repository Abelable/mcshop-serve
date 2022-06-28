<?php

namespace App\Services\Promotion;

use App\CodeResponse;
use App\Enums\CouponEnums;
use App\Input\PageInput;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponUser;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CouponService extends BaseService
{
    public function list(PageInput $input, $columns = ['*'])
    {
        return Coupon::query()
            ->where('type', CouponEnums::TYPE_COMMON)
            ->where('status', CouponEnums::STATUS_NORMAL)
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }

    public function mylist(int $userId, int $status, PageInput $input, $columns = ['*'])
    {
        return CouponUser::query()
            ->where('user_id', $userId)
            ->when(!is_null($status), function (Builder $query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }

    public function getCoupons(array $ids, $columns = ['*'])
    {
        return Coupon::query()->whereIn('id', $ids)->get($columns);
    }

    public function receive(int $couponId, int $userId)
    {
        $coupon = $this->getCoupon($couponId);

        if (is_null($coupon)) {
            $this->throwBusinessException(CodeResponse::NOT_FOUND);
        }

        // 判断优惠券类型，是否可领取
        if ($coupon->type != CouponEnums::TYPE_COMMON) {
            $this->throwBusinessException(CodeResponse::COUPON_RECEIVE_FAIL, '优惠券类型不支持');
        }

        // 判读优惠券状态
        $status = $coupon->status;
        if ($status == CouponEnums::STATUS_EXPIRED) {
            $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT, '优惠券已过期');
        }
        if ($status == CouponEnums::STATUS_OUT) {
            $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT, '优惠券已领完');
        }

        // 判断优惠券是否已领完
        if ($coupon->total > 0) {
            $receivedCount = $this->getCouponReceivedCount($couponId);
            if ($receivedCount > $coupon->total) {
                $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT);
            }
        }

        // 判断用户领取数量是否已超过限领数量
        if ($coupon->limit > 0) {
            $userReceivedCount = $this->getUserReceiverdCount($couponId, $userId);
            if ($userReceivedCount > $coupon->limit) {
                $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT, '优惠券已经领取过');
            }
        }

        $couponUser = CouponUser::new();
        if ($coupon->time_type == CouponEnums::TIME_TYPE_TIME) {
            $startTime = $coupon->start_time;
            $endTime = $coupon->end_time;
        } else {
            $startTime = Carbon::now();
            $endTime = $startTime->copy()->addDays($coupon->days);
        }
        $couponUser->fill([
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
        return $couponUser->save();
    }

    public function getCoupon(int $id, $columns = ['*'])
    {
        return Coupon::query()->find($id, $columns);
    }

    public function getCouponReceivedCount(int $couponId)
    {
        return CouponUser::query()->where('coupon_id', $couponId)->count('id');
    }

    public function getUserReceiverdCount(int $couponId, int $userId)
    {
        return CouponUser::query()
            ->where('coupon_id', $couponId)
            ->where('user_id', $userId)
            ->count('id');
    }
}
