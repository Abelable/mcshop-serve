<?php

namespace App\Utils\Inputs;

use App\Exceptions\BusinessException;
use App\Utils\CodeResponse;
use Illuminate\Support\Facades\Validator;
use function collect;
use function request;

class Input
{
    /**
     * @param null|array $data
     * @return Input
     * @throws BusinessException
     */
    public static function new($data = null)
    {
        return (new static())->fill($data);
    }

    /**
     * @param null|array $data
     * @return $this
     * @throws BusinessException
     */
    public function fill($data = null)
    {
        if (is_null($data)) {
            $data = request()->input();
        }

        $validator = Validator::make($data, $this->rules());
        if ($validator->fails()) {
            throw new BusinessException(CodeResponse::PARAM_ILLEGAL);
        }

        $map = get_object_vars($this);
        $keys = array_keys($map);
        collect($data)->map(function ($v, $k) use ($keys) {
            if (in_array($k, $keys)) {
                $this->$k = $v;
            }
        });
        return $this;
    }

    public function rules()
    {
        return [];
    }
}
