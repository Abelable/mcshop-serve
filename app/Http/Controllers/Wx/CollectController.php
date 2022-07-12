<?php

namespace App\Http\Controllers\Wx;

use App\Models\Collect;
use App\Models\Goods\Goods;
use App\Services\CollectService;
use App\Services\Goods\GoodsService;
use App\Utils\Inputs\PageInput;

class CollectController extends WxController
{
    public function getList()
    {
        $input = PageInput::new();
        $paginate = CollectService::getInstance()->getList($input, $this->userId());
        $collectList = collect($paginate->items());
        $goodsIds = $collectList->pluck('value_id')->toArray();
        $goodsList = GoodsService::getInstance()->getGoodsListByIds($goodsIds)->keyBy('id');
        $list = $collectList->map(function (Collect $collect) use ($goodsList) {
            /** @var Goods $goods */
            $goods = $goodsList->get($collect->value_id);
            return [
              'id' => $collect->id,
              'type' => $collect->type,
              'valueId' => $collect->value_id,
              'name' => $goods->name,
              'brief' => $goods->brief,
              'picUrl' => $goods->pic_url,
              'retailPrice' => $goods->retail_price
            ];
        });
        return $this->successPaginate($paginate, $list);
    }

    public function addOrDelete()
    {
        $type = $this->verifyInteger('type', 0);
        $valueId = $this->verifyInteger('valueId');
        CollectService::getInstance()->addOrDelete($this->userId(), $type, $valueId);
        return $this->success();
    }
}
