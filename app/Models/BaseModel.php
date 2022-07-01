<?php

namespace App\Models;

use App\Utils\Traits\BooleanSoftDeletes;
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

    /**
     * 乐观锁更新 compare and save
     *
     * @return bool|int
     * @throws \Throwable
     */
    public function cas()
    {
        // 数据不存在时，禁止更新操作
        throw_if(!$this->exists, \Exception::class, 'model not exists when cas');

        // 当内存中跟新数据为空时，禁止更新操作
        $dirty = $this->getDirty();
        if (empty($dirty)) {
            return 0;
        }

        // 当模型开启自动更新时间字段时，附上更新时间字段
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
            $dirty = $this->getDirty();
        }

        $diff = array_diff(array_keys($dirty), array_keys($this->original));
        throw_if(!empty($diff), \Exception::class, 'key [ ' . implode(',', $diff) . ' ] is not exist');

        if ($this->fireModelEvent('casing') === false) {
            return 0;
        }

        // 使用newModelQuery更新的时候不用带上 delete = 0 的条件
        $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());

        foreach ($dirty as $key => $value) {
            $query->where($key, $this->getOriginal($key)); // 判断一下更新的字段值是否有改动
        }

        $rows = $query->update($dirty);
        if ($rows > 0) {
            $this->syncChanges();
            $this->fireModelEvent('cased', false);
            $this->syncOriginal();
        }

        return $rows;
    }

    /**
     * Register a casing model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function casing($callback)
    {
        static::registerModelEvent('casing', $callback);
    }

    /**
     * Register a cased model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function cased($callback)
    {
        static::registerModelEvent('cased', $callback);
    }
}
