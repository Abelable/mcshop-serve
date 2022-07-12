<?php

namespace App\Services;

use App\Models\Ad;

class AdService extends BaseService
{
    public function queryIndex()
    {
        return Ad::query()->where('position', 1)->where('enabled', 1)->get();
    }
}
