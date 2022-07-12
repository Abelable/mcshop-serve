<?php

namespace App\Services;

use App\Models\Collect;
use App\Utils\Enums\Constant;
use Illuminate\Support\Carbon;

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

    public function getList(PageInput $input, int $userId, $columns = ['*'])
    {
        return Collect::query()->where('user_id', $userId)->paginate($input->limit, $columns, 'page', $input->page);
    }

    public function addOrDelete(int $userId, int $type, int $valueId)
    {
        $conditions = [
            'user_id' => $userId,
            'type' => $type,
            'value_id' => $valueId
        ];
        $collectList = Collect::query()->where($conditions)->get();
        if (is_null($collectList)) {
            return Collect::query()->where($conditions)->delete();
        } else {
            $collect = Collect::new();
            $collect->type = $type;
            $collect->value_id = $valueId;
            $collect->user_id = $userId;
            $collect->add_time = Carbon::now()->toDateTimeString();
            return $collect->save();
        }
    }
}
