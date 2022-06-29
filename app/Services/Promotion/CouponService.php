<?php

namespace App\Services\Promotion;

use App\CodeResponse;
use App\Enums\CouponEnums;
use App\Enums\CouponUserEnums;
use App\Input\PageInput;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponUser;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use function Symfony\Component\Translation\t;

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
            $userReceivedCount = $this->getUserReceivedCount($couponId, $userId);
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

    public function getUserReceivedCount(int $couponId, int $userId)
    {
        return CouponUser::query()
            ->where('coupon_id', $couponId)
            ->where('user_id', $userId)
            ->count('id');
    }

    public function getCouponUser(int $id, $columns = ['*'])
    {
        return CouponUser::query()->find($id, $columns);
    }

    public function getPreorderCouponUser(int $userId, $couponId, $couponUserId, $price, &$usableCouponCount)
    {
        $couponUsers = $this->getPreorderCouponUsers($userId, $price);
        $usableCouponCount = $couponUsers->count();

        // 这里存在三种情况
        // 1. 用户不想使用优惠券，则不处理
        // 2. 用户想自动使用优惠券，则选择合适优惠券
        // 3. 用户已选择优惠券，则测试优惠券是否可用；不可用，则返回用户合适优惠券
        if (is_null($couponId) || $couponId == -1) {
            return null;
        }
        if (!empty($couponId)) {
            $coupon = $this->getCoupon($couponId);
            $couponUser = $this->getCouponUser($couponUserId);
            $isUsable = $this->checkCouponUsable($coupon, $couponUser, $price);
            if ($isUsable) {
                return $couponUser;
            }
        }
        return $couponUsers->filter();
    }

    public function getPreorderCouponUsers(int $userId, $price)
    {
        $couponUsers = $this->getUsableCouponUsers($userId);
        $couponIds = $couponUsers->pluck('coupon_id')->toArray();
        $coupons = $this->getCoupons($couponIds)->keyBy('id');
        return $couponUsers->filter(function (CouponUser $couponUser) use ($coupons, $price) {
            $coupon = $coupons->get($couponUser->coupon_id);
            return $this->checkCouponUsable($coupon, $couponUser, $price);
        })->sortByDesc(function (CouponUser $couponUser) use ($coupons) {
            /** @var Coupon $coupon */
            $coupon = $coupons->get($couponUser->coupon_id);
            return $coupon->discount;
        });
    }

    public function getUsableCouponUsers(int $userId)
    {
        return CouponUser::query()
            ->where('user_id', $userId)
            ->where('status', CouponUserEnums::STATUS_USABLE)
            ->get();
    }

    public function checkCouponUsable(Coupon $coupon, CouponUser $couponUser, $price)
    {
        if (empty($coupon) || empty($couponUser)) {
            return false;
        }
        if ($coupon->id != $couponUser->coupon_id) {
            return false;
        }
        if ($coupon->status != CouponEnums::STATUS_NORMAL) {
            return false;
        }
        if ($coupon->goods_type != CouponEnums::GOODS_TYPE_ALL) {
            return false;
        }
        if (bccomp($coupon->min, $price) == 1) {
            return false;
        }

        $now = now();
        switch ($coupon->time_type) {
            case CouponEnums::TIME_TYPE_TIME:
                $start = Carbon::parse($coupon->start_time);
                $end = Carbon::parse($coupon->end_time);
                if ($now->isBefore($start) || $now->isAfter($end)) {
                    return false;
                }
                break;
            case CouponEnums::TIME_TYPE_DAYS:
                $expired = Carbon::parse($couponUser->add_time)->addDays($coupon->days);
                if ($now->isAfter($expired)) {
                    return false;
                }
            default:
                return false;
        }

        return true;
    }
}
