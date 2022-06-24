<?php

namespace App\Models\Goods;

use App\Models\BaseModel;

class Footprint extends BaseModel
{
    protected $fillable = [
        'user_id',
        'goods_id'
    ];
}
