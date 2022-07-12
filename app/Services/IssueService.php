<?php

namespace App\Services;

use App\Models\Issue;
use App\Utils\Inputs\PageInput;

class IssueService extends BaseService
{
    public function getList(PageInput $input, $columns = ['*'])
    {
        return Issue::query()->paginate($input->limit, $columns, 'page', $input->page);
    }
}
