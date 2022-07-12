<?php

namespace App\Http\Controllers\Wx;

use App\Services\TopicService;
use App\Utils\Inputs\PageInput;

class TopicController extends WxController
{
    public function getList()
    {
        $input = PageInput::new();
        $list = TopicService::getInstance()->getList($input);
        return $this->successPaginate($list);
    }

    public function getDetail()
    {
        $id = $this->verifyRequiredId('id');
        list($topic, $goodsList) = TopicService::getInstance()->getDetail($id);
        return $this->success(compact('topic', 'goodsList'));
    }

    public function getRelated()
    {
        $id = $this->verifyRequiredId('id');
        $list = TopicService::getInstance()->getRelated($id);
        return $this->success($list);
    }
}
