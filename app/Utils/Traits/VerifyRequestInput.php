<?php

namespace App\Utils\Traits;


use App\Exceptions\BusinessException;
use App\Utils\CodeResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use function request;

trait VerifyRequestInput
{
    public function verifyId($key, $default = null)
    {
        return $this->verifyData($key, $default, 'integer|digits_between:1,20');
    }

    public function verifyRequiredId($key, $default = null)
    {
        return $this->verifyData($key, $default, 'required|integer|digits_between:1,20');
    }

    public function verifyString($key, $default = null)
    {
        return $this->verifyData($key, $default, 'string');
    }

    public function verifyRequiredString($key, $default = null)
    {
        return $this->verifyData($key, $default, 'required|string');
    }

    public function verifyBoolean($key, $default = null)
    {
        return $this->verifyData($key, $default, 'boolean');
    }

    public function verifyInteger($key, $default = null)
    {
        return $this->verifyData($key, $default, 'integer');
    }

    public function verifyPositiveInteger($key, $default = null)
    {
        return $this->verifyData($key, $default, 'integer|min:1');
    }

    public function verifyEnum($key, $default = null, $enum = [])
    {
        return $this->verifyData($key, $default, Rule::in($enum));
    }

    public function verifyArrayNotEmpty($key, $default = null)
    {
        return $this->verifyData($key, $default, 'array|min:1');
    }

    public function verifyMobile()
    {
        return $this->verifyData('mobile', null, 'required|regex:/^1[345789][0-9]{9}$/');
    }

    public function verifyData($key, $default, $rule)
    {
        $value = request()->input($key, $default);
        $validator = Validator::make([$key => $value], [$key => $rule]);
        if (is_null($default) && is_null($value)) {
            return $value;
        }
        if ($validator->fails()) {
            throw new BusinessException(CodeResponse::PARAM_ILLEGAL);
        }
        return $value;
    }
}
