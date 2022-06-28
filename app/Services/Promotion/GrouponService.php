<?php

namespace App\Services\Promotion;

use App\CodeResponse;
use App\Enums\GrouponEnums;
use App\Input\PageInput;
use App\Models\Promotion\Groupon;
use App\Models\Promotion\GrouponRules;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\AbstractFont;
use Intervention\Image\Facades\Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GrouponService extends BaseService
{
    public function getGrouponRuleList(PageInput $input, $columns = ['*'])
    {
        return GrouponRules::query()
            ->where('status', GrouponEnums::STATUS_ON)
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }

    public function getGrouponRule(int $ruleId, $columns = ['*'])
    {
        return GrouponRules::query()->find($ruleId, $columns);
    }

    public function checkGrouponRuleValid(int $userId, $ruleId, $linkId = null)
    {
        if ($ruleId == null || $ruleId < 0) {
            return;
        }

        $rule = $this->getGrouponRule($ruleId);
        if (is_null($rule)) {
            $this->throwBusinessException(CodeResponse::NOT_FOUND);
        }

        if ($rule->status == GrouponEnums::RULE_STATUS_DOWN_EXPIRE) {
            $this->throwBusinessException(CodeResponse::GROUPON_EXPIRED);
        }
        if ($rule->status == GrouponEnums::RULE_STATUS_DOWN_ADMIN) {
            $this->throwBusinessException(CodeResponse::GROUPON_OFFLINE);
        }

        if ($linkId == null || $linkId <= 0) {
            return;
        }
        if ($this->countGrouponJoin($linkId) >= ($rule->discount_member - 1)) {
            $this->throwBusinessException(CodeResponse::GROUPON_FULL);
        }
        if ($this->isOpenOrJoin($userId, $linkId)) {
            $this->throwBusinessException(CodeResponse::GROUPON_JOIN);
        }
    }

    public function countGrouponJoin($grouponId)
    {
        return Groupon::query()
            ->where('groupon_id', $grouponId)
            ->where('status', '!=', GrouponEnums::STATUS_NONE)
            ->count('id');
    }

    public function isOpenOrJoin(int $userId, int $grouponId)
    {
        return Groupon::query()
            ->where('user_id', $userId)
            ->where(function (Builder $query) use ($grouponId) {
                return $query->where('groupon_id', $grouponId)->orWhere('id', $grouponId);
            })
            ->where('status', '!=', GrouponEnums::STATUS_NONE)
            ->exists();
    }

    public function openOrJoinGroupon(int $userId, int $orderId, int $ruleId, $linkId = null)
    {
        if ($ruleId == null || $ruleId <= 0) {
            return $linkId;
        }

        $groupon = Groupon::new();
        $groupon->user_id = $userId;
        $groupon->order_id = $orderId;
        $groupon->rules_id = $ruleId;
        $groupon->status = GrouponEnums::STATUS_NONE;

        // 没有linkId，表示开团
        if ($linkId == null || $linkId <= 0) {
            $groupon->creator_user_id = $userId;
            $groupon->creator_user_time = Carbon::now()->toDateTimeString();
            $groupon->groupon_id = 0;
            $groupon->save();
            return $groupon->id;
        }

        // 参团
        $openGroupon = $this->getGroupon($linkId);
        $groupon->creator_user_id = $openGroupon->creator_user_id;
        $groupon->groupon_id = $linkId;
        $groupon->share_url = $openGroupon->share_url;
        $groupon->save();
        return $linkId;
    }

    public function getGroupon(int $id, $columns = ['*'])
    {
        return Groupon::query()->find($id, $columns);
    }

    public function payGrouponOrder(int $orderId)
    {
        $groupon = $this->getGrouponByOrderId($orderId);
        if (is_null($groupon)) {
            $this->throwBusinessException(CodeResponse::NOT_FOUND);
        }

        $rule = $this->getGrouponRule($groupon->rules_id);

        // 开团状态下
        if ($groupon->groupon_id == 0) {
            $groupon->share_url = $this->createGrouponShareImage($rule);
        }

        $groupon->status = GrouponEnums::STATUS_ON;
        $isSuccess = $groupon->save();
        if (!$isSuccess) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }

        if ($groupon->groupon_id == 0) {
            return;
        }

        // 小于开团人数，直接返回
        $joinCount = $this->countGrouponJoin($groupon->groupon_id);
        if ($joinCount < ($rule->discount_member - 1)) {
            return;
        }

        // 达到开团人数，团购成功，安排发货
        $rows = Groupon::query()->where(function (Builder $query) use ($groupon) {
            return $query->where('groupon_id', $groupon->groupon_id)->orWhere('id', $groupon->groupon_id);
        })->update(['status' => GrouponEnums::STATUS_SUCCEED]);
        if ($rows == 0) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }
    }

    public function getGrouponByOrderId(int $orderId, $columns = ['*'])
    {
        return Groupon::query()->where('order_id', $orderId)->first($columns);
    }

    public function createGrouponShareImage(GrouponRules $rules)
    {
        $shareUrl = route('home.redirectShareUrl', ['type' => 'groupon', 'id' => $rules->goods_id]);
        $qrcode = QrCode::format('png')->margin(1)->size(290)->generate($shareUrl);
        $goodsImage = Image::make($rules->pic_url)->resize(660, 660);

        $image = Image::make(resource_path('images/back_groupon.png'))
            ->insert($qrcode, 'top-left', 460, 770)
            ->insert($goodsImage, 'top-left', 71, 69)
            ->text($rules->goods_name, 65, 867, function (AbstractFont $font) {
                $font->color(array(167, 136, 69));
                $font->file(resource_path('ttf/msyh.ttf'));
                $font->size(28);
            });

        $filePath = 'groupon/' . Carbon::now()->toDateString() . '/' . Str::random() . '.png';
        Storage::disk('public')->put($filePath, $image->encode());

        return Storage::url($filePath);
    }
}
