<?php

namespace App;

class CodeResponse
{
    const SUCCESS = [0, '成功'];
    const FAIL = [-1, '失败'];
    const PARAM_ILLEGAL = [401, '参数不合法'];
    const PARAM_VALUE_ILLEGAL = [402, '参数值不对'];
    const UN_LOGIN = [501, '未登录'];
}
