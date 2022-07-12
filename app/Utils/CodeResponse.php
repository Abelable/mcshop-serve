<?php

namespace App\Utils;

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

    const GOODS_UNSHELVE = [710, '商品已经下架!'];
    const GOODS_NO_STOCK = [711, '商品库存不足!'];

    const ORDER_PAY_FAIL = [724, '订单支付失败'];
    const ORDER_INVALID_OPERATION = [725, '订单操作失败'];

    const GROUPON_EXPIRED = [730, '团购已过期!'];
    const GROUPON_OFFLINE = [731, '团购已下线!'];
    const GROUPON_FULL = [732, '参团人数已满!'];
    const GROUPON_JOIN = [733, '团购活动已经参加!'];

    const COUPON_EXCEED_LIMIT = [740, '优惠券已领完'];
    const COUPON_RECEIVE_FAIL = [741, '优惠券领取失败'];
}
