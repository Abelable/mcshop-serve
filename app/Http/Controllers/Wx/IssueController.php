<?php

namespace App\Http\Controllers\Wx;

use App\Services\IssueService;
use App\Utils\Inputs\PageInput;

class IssueController extends WxController
{
    public function getList()
    {
        $input = PageInput::new();
        $list = IssueService::getInstance()->getList($input);
        return $this->successPaginate($list);
    }
}
