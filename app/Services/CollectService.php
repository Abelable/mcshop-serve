<?php

namespace App\Services;

use App\Models\Collect;
use App\Utils\Enums\Constant;

class CollectService extends BaseService
{
    public function getGoodsIsCollected(int $userId, int $goodsId)
    {
        return Collect::query()
            ->where('user_id', $userId)
            ->where('value_id', $goodsId)
            ->where('type', Constant::COLLECT_TYPE_GOODS)
            ->count('id');
    }
}
