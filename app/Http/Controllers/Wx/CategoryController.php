<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Services\Goods\CategoryService;

class CategoryController extends WxController
{
    protected $only = [];

    public function index()
    {
        $id = $this->verifyId('id');
        $categoryList = CategoryService::getInstance()->getL1List();

        if (empty($id)) {
            $currentCategory = $categoryList->first();
        } else {
            $currentCategory = $categoryList->where('id', $id)->first();
        }

        $currentSubCategory = [];
        if (!is_null($currentCategory)) {
            $currentSubCategory = CategoryService::getInstance()->getL2ListByPid($currentCategory->id);
        }

        return $this->success(compact('currentCategory', 'categoryList', 'currentSubCategory'));
    }

    public function current()
    {
        $id = $this->verifyRequiredId('id');
        $categoryList = CategoryService::getInstance()->getL1List();
        $currentCategory = $categoryList->where('id', $id)->first();
        if (is_null($currentCategory)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }
        $currentSubCategory = CategoryService::getInstance()->getL2ListByPid($currentCategory->id);
        return $this->success(compact('currentCategory', 'currentSubCategory'));
    }
}
