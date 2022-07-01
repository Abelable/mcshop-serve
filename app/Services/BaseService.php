<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Utils\CodeResponse;

class BaseService
{
    protected static $instance;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (static::$instance instanceof static) {
            return static::$instance;
        }
        static::$instance = new static();
        return static::$instance;
    }

    // 限制只能通过getInstance生成实例
    private function __construct() {}
    private function __clone() {}

    public function throwBadArgumentValue()
    {
        $this->throwBusinessException(CodeResponse::PARAM_VALUE_ILLEGAL);
    }

    public function throwUpdateFail()
    {
        $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
    }

    /**
     * @param array $codeResponse
     * @param string $info
     * @throws BusinessException
     */
    public function throwBusinessException(array $codeResponse, $info = '')
    {
        throw new BusinessException($codeResponse, $info);
    }
}
