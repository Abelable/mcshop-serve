<?php

namespace App\Http\Controllers\Wx;

use App\Services\AdService;
use App\Services\Goods\BrandService;
use App\Services\Goods\CategoryService;
use App\Services\Goods\GoodsService;
use App\Services\Promotion\CouponService;
use App\Services\Promotion\GrouponService;
use App\Services\SystemService;
use App\Services\TopicService;
use Illuminate\Support\Facades\Cache;

class HomeController extends WxController
{
    protected $only = [];

    public function index()
    {
        $key = 'index_data';
        $indexData = Cache::get($key);
        if (!empty($indexData)) {
            return $this->success(json_decode($indexData, true));
        }

        $bannerList = AdService::getInstance()->queryIndex();
        $channelList = CategoryService::getInstance()->getL1List(['id', 'name', 'icon_url']);
        $newGoodsList = GoodsService::getInstance()->getNewGoodsList(SystemService::getInstance()->getNewGoodsLimit());
        $hotGoodsList = GoodsService::getInstance()->getHotGoodsList(SystemService::getInstance()->getHotGoodsLimit());
        $brandList = BrandService::getInstance()->getBrandListByLimit(SystemService::getInstance()->getBrandLimit());
        $topicList = TopicService::getInstance()->getTopicListByLimit(SystemService::getInstance()->getTopicLimit());
        $grouponList = GrouponService::getInstance()->getGrouponListByLimit();
        if ($this->isLogin()) {
            $couponList = CouponService::getInstance()->getAvailableList($this->userId());
        } else {
            $couponList = CouponService::getInstance()->getCouponList();
        }

        $data = [
            'banner' => $bannerList,
            'channel' => $channelList,
            'couponList' => $couponList,
            'newGoodsList' => $newGoodsList,
            'hotGoodsList' => $hotGoodsList,
            'brandList' => $brandList,
            'topicList' => $topicList,
            'grouponList' => $grouponList,
            'floorGoodsList' => []
        ];

        Cache::put($key, json_encode($data), 60 * 60);
        return $this->success($data);
    }

    public function redirectShareUrl()
    {
        $type = $this->verifyString('type', 'groupon');
        $id = $this->verifyId('id');

        if ($type == 'groupon') {
            return redirect()->to(env('H5_URL') . '/#/items/detail/' . $id);
        }
    }
}
