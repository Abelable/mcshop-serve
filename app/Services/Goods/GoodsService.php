<?php

namespace App\Services\Goods;

use App\Input\GoodsListInput;
use App\Models\Goods\Footprint;
use App\Models\Goods\Goods;
use App\Models\Goods\GoodsAttribute;
use App\Models\Goods\GoodsProduct;
use App\Models\Goods\GoodsSpecification;
use App\Models\Goods\Issue;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;

class GoodsService extends BaseService
{
    public function countGoodsOnSale()
    {
        return Goods::query()->where('is_on_sale', 1)->count('id');
    }

    public function getGoodsList(GoodsListInput $input, $columns = ['*'])
    {
        $query = $this->getQueryByGoodsFilters($input);
        if (!empty($input->categoryId)) {
            $query = $query->where('category_id', $input->categoryId);
        }
        return $query->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }

    public function getCategoryIds(GoodsListInput $input)
    {
        $query = $this->getQueryByGoodsFilters($input);
        $ids = $query->select(['category_id'])->pluck('category_id')->unique()->toArray();
        return $ids;
    }

    private function getQueryByGoodsFilters(GoodsListInput $input)
    {
        $query = Goods::query()->where('is_on_sale', 1);
        if (!empty($input->brandId)) {
            $query = $query->where('brand_id', $input->brandId);
        }
        if (!is_null($input->isNew)) {
            $query = $query->where('is_new', $input->isNew);
        }
        if (!is_null($input->isHot)) {
            $query = $query->where('is_hot', $input->isHot);
        }
        if (!empty($input->keywords)) {
            $query = $query->where(function (Builder $query) use ($input) {
                $query->where('keywords', 'like', "%$input->keywords%")
                    ->orWhere('name', 'like', "%$input->keywords%");
            });
        }
        return $query;
    }

    public function getGoods(int $id)
    {
        return Goods::query()->find($id);
    }

    public function getGoodsAttributeList(int $goodsId)
    {
        return GoodsAttribute::query()->where('goods_id', $goodsId)->get();
    }

    public function getGoodsSpecification(int $goodsId)
    {
        $specification = GoodsSpecification::query()->where('goods_id', $goodsId)->get();
        return $specification
            ->groupBy('specification')
            ->map(function ($v, $k) {
                return [
                    'name' => $k,
                    'valueList' => $v
                ];
            })
            ->values();
    }

    public function getGoodsProductList(int $goodsId)
    {
        return GoodsProduct::query()->where('goods_id', $goodsId)->get();
    }

    public function getGoodsIssue($page = 1, $limit = 4)
    {
        return Issue::query()->forPage($page, $limit)->get();
    }

    public function saveFootprint(int $userId, int $goodsId)
    {
        $footprint = Footprint::new();
        $footprint->fill([
            'user_id' => $userId,
            'goods_id' => $goodsId
        ]);
        return $footprint->save();
    }

    public function getGoodsListByIds(array $ids)
    {
        if (empty($ids)) {
            return collect([]);
        }
        return Goods::query()->whereIn('id', $ids)->get();
    }
}
