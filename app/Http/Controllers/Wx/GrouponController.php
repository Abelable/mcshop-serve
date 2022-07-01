<?php

namespace App\Http\Controllers\Wx;

use App\Models\Goods\Goods;
use App\Models\Promotion\GrouponRules;
use App\Services\Goods\GoodsService;
use App\Services\Promotion\GrouponService;
use App\Utils\Inputs\PageInput;

class GrouponController extends WxController
{
    protected $except = ['list'];

    public function list()
    {
        $input = PageInput::new();
        $paginate = GrouponService::getInstance()->getGrouponRuleList($input);
        $ruleList = collect($paginate->items());

        $goodsIds = $ruleList->pluck('goods_id')->toArray();
        $goodsList = GoodsService::getInstance()->getGoodsListByIds($goodsIds)->keyBy('id');

        $list = $ruleList->map(function (GrouponRules $rule) use ($goodsList) {
            /** @var Goods $goods */
            $goods = $goodsList->get($rule->goods_id);
            return [
                'id' => $goods->id,
                'name' => $goods->name,
                'brief' => $goods->brief,
                'picUrl' => $goods->pic_url,
                'counterPrice' => $goods->counter_price,
                'retailPrice' => $goods->retail_price,
                'grouponPrice' => bcsub($goods->retail_price, $rule->discount, 2), // bcsub: 减法，精度工具函数
                'grouponDiscount' => $rule->discount,
                'grouponMember' => $rule->discount_member,
                'expireTime' => $rule->expire_time
            ];
        });

        return $this->success($this->paginate($paginate, $list));
    }
}
