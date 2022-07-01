<?php

namespace App\Services\Goods;

use App\Models\Goods\Brand;
use App\Services\BaseService;
use App\Utils\Inputs\PageInput;

class BrandService extends BaseService
{
    public function getBrandList(PageInput $page, $columns = ['*'])
    {
        return Brand::query()
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);
    }

    public function getBrand(int $id)
    {
        return Brand::query()->find($id);
    }
}
