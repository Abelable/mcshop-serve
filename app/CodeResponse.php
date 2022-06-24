<?php

namespace App;

class CodeResponse
{
    const SUCCESS = [0, '成功'];
    const FAIL = [-1, '失败'];
    const PARAM_ILLEGAL = [401, '参数不合法'];
    const PARAM_VALUE_ILLEGAL = [402, '参数值不对'];
    const NOT_FOUND = [404, '数据不存在'];
    const UN_LOGIN = [501, '未登录'];
    const SYSTEM_ERROR = [502, '系统内部错误'];
    const UPDATED_FAIL = [505, '数据更新失败'];

    const AUTH_INVALID_ACCOUNT = [700, '账号不存在'];
    const AUTH_NAME_REGISTERED = [704, '用户已注册'];
    const AUTH_MOBILE_REGISTERED = [705, '手机号码已经注册'];
    const AUTH_CAPTCHA_FREQUENCY = [702, '验证码未超时1分钟，不能发送'];
    const AUTH_CAPTCHA_UNMATCH = [703, '验证码错误'];

}
