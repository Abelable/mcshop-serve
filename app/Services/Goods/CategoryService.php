<?php

namespace App\Services\Goods;

use App\Models\Goods\Category;
use App\Services\BaseService;

class CategoryService extends BaseService
{
    public function getL1List($columns = ['*'])
    {
        return Category::query()->where('level', 'L1')->get($columns);
    }

    public function getL2ListByPid(int $pid)
    {
        return Category::query()->where('pid', $pid)->where('level', 'L2')->get();
    }

    public function getL1byId(int $id)
    {
        return Category::query()->where('id', $id)->where('level', 'L1')->first();
    }

    public function getCategoryById(int $id)
    {
        return Category::query()->find($id);
    }

    public function getL2ListByIds(array $ids)
    {
        return Category::query()->whereIn('id', $ids)->get();
    }
}
