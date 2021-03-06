<?php

namespace App\Http\Controllers\Wx;

use App\Services\CollectService;
use App\Services\CommentService;
use App\Services\Goods\BrandService;
use App\Services\Goods\CategoryService;
use App\Services\Goods\GoodsService;
use App\Services\SearchHistoryService;
use App\Utils\CodeResponse;
use App\Utils\Enums\Constant;
use App\Utils\Inputs\GoodsListInput;

class GoodsController extends WxController
{
    protected $only = [];

    public function count()
    {
        $count = GoodsService::getInstance()->countGoodsOnSale();
        return $this->success($count);
    }

    public function category()
    {
        $id = $this->verifyRequiredId('id');
        $currentCategory = CategoryService::getInstance()->getCategoryById($id);
        if (is_null($currentCategory)) {
            return $this->fail(CodeResponse::NOT_FOUND);
        }

        if ($currentCategory->pid == 0) {
            $parentCategory = $currentCategory;
            $brotherCategory = CategoryService::getInstance()->getL2ListByPid($currentCategory->id);
            $currentCategory = $brotherCategory->first() ?? $currentCategory;
        } else {
            $parentCategory = CategoryService::getInstance()->getL1byId($currentCategory->pid);
            $brotherCategory = CategoryService::getInstance()->getL2ListByPid($currentCategory->pid);
        }

        return $this->success(compact('currentCategory', 'parentCategory', 'brotherCategory'));
    }

    public function list()
    {
        /** @var GoodsListInput $input */
        $input = GoodsListInput::new();

        if ($this->isLogin() && !empty($input->keyword)) {
            SearchHistoryService::getInstance()->save($this->userId(), $input->keyword, Constant::SEARCH_HISTORY_FROM_WX);
        }

        $columns = ['id', 'name', 'brief', 'pic_url', 'is_new', 'is_hot', 'counter_price', 'retail_price'];
        $goodsList = GoodsService::getInstance()->getGoodsList($input, $columns);
        $categoryIds = GoodsService::getInstance()->getCategoryIds($input);
        $categoryList = CategoryService::getInstance()->getL2ListByIds($categoryIds);

        $list = $this->paginate($goodsList);
        $list['filterCategoryList'] = $categoryList;
        return $this->success($list);
    }

    public function detail()
    {
        $id = $this->verifyRequiredId('id');

        // ????????????
        $info = GoodsService::getInstance()->getGoods($id);
        if (is_null($info)) {
            return $this->fail(CodeResponse::NOT_FOUND);
        }

        // ????????????
        $attributeList = GoodsService::getInstance()->getGoodsAttributeList($id);

        // ????????????
        $specification = GoodsService::getInstance()->getGoodsSpecification($id);

        // ????????????
        $productList = GoodsService::getInstance()->getGoodsProductList($id);

        // ????????????
        $issue = GoodsService::getInstance()->getGoodsIssue();

        // ???????????????
        $brand = $info->brand_id ? BrandService::getInstance()->getBrand($info->brand_id) : (object) []; // (object) []???????????????

        // ????????????
        $comment = CommentService::getInstance()->getCommentWithUserInfo($id);

        // ???????????????????????????????????????
        $userHasCollect = 0;
        if ($this->isLogin()) {
            $userHasCollect = CollectService::getInstance()->getGoodsIsCollected($this->userId(), $id);
            GoodsService::getInstance()->saveFootprint($this->userId(), $id);
        }

        return $this->success([
            'info' => $info,
            'attribute' => $attributeList,
            'specificationList' => $specification,
            'productList' => $productList,
            'issue' => $issue,
            'brand' => $brand,
            'comment' => $comment,
            'userHasCollect' => $userHasCollect,
            'groupon' => [],
            'share' => false,
            'shareImage' => $info->share_url
        ]);
    }
}
