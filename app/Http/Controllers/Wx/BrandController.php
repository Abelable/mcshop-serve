<?php

namespace App\Http\Controllers\Wx;

use App\Services\Goods\BrandService;
use App\Utils\CodeResponse;
use App\Utils\Inputs\PageInput;

class BrandController extends WxController
{
    protected $only = [];

    public function list()
    {
        $input = PageInput::new();
        $list = BrandService::getInstance()->getBrandList($input, ['id', 'name', 'desc', 'pic_url', 'floor_price']);
        return $this->successPaginate($list);
    }

    public function detail()
    {
        $id = $this->verifyRequiredId('id', 0);
        $brand = BrandService::getInstance()->getBrand($id);
        if (is_null($brand)) {
            return $this->fail(CodeResponse::NOT_FOUND);
        }
        return $this->success($brand);
    }
}
