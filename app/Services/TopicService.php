<?php

namespace App\Services;

use App\Models\Topic;
use App\Services\Goods\GoodsService;
use App\Utils\Inputs\PageInput;
use Illuminate\Database\Eloquent\Builder;

class TopicService extends BaseService
{
    public function getList(PageInput $input, $columns = ['*'])
    {
        return Topic::query()->paginate($input->limit, $columns, 'page', $input->page);
    }

    public function getDetail(int $id)
    {
        $goodsList = [];
        $topic = Topic::query()->find($id);
        if (empty($topic)) {
            return array($topic, $goodsList);
        }
        $goodsIds = json_decode($topic->goods, true);
        if (!empty($goodsIds)) {
            $goodsList = GoodsService::getInstance()->getGoodsListByIds($goodsIds)->toArray();
        }
        return array($topic, $goodsList);
    }

    public function getRelated(int $id, $offset = 0, $limit = 4, $sort = 'add_time', $order = 'desc')
    {
        $topic = Topic::query()->find($id);
        return Topic::query()->when(!empty($topic), function (Builder $query) use ($topic) {
            return $query->whereNotIn('id', array($topic->id));
        })->offset($offset)->limit($limit)->orderBy($sort, $order)->get();
    }

    public function getTopicListByLimit(int $limit, $offset = 0, $order = 'desc', $sort = 'add_time')
    {
        return Topic::query()->offset($offset)->limit($limit)->orderBy($sort, $order)->get();
    }
}
