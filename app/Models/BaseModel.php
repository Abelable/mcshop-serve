<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * App\Models\BaseModel
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel query()
 * @mixin \Eloquent
 */
class BaseModel extends Model
{
    use BooleanSoftDeletes;

    public const CREATED_AT = 'add_time';
    public const UPDATED_AT = 'update_time';

    public $defaultCasts = ['deleted' => 'boolean'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        parent::mergeCasts($this->defaultCasts); // 数据类型转换
    }

    public static function new()
    {
        return new static();
    }

    /**
     * 重写getTable方法，对应数据库表名
     * @return string
     */
    public function getTable()
    {
        // 驼峰转下划线：Str::snake
        return $this->table ?? Str::snake(class_basename($this));
    }

    /**
     * 重写toArray方法，下划线转驼峰
     * @return array|false
     */
    public function toArray()
    {
        $items = parent::toArray();
        $items = array_filter($items, function ($item) {
            return !is_null($item);
        });
        $keys = array_keys($items);
        $keys = array_map(function ($key) {
            // 1.转驼峰: Str::studly
            // 2.首字母小写: lcfirst
            return lcfirst(Str::studly($key));
        }, $keys);
        $values = array_values($items);
        return array_combine($keys, $values);
    }
}
