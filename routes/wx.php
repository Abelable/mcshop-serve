<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 用户模块
Route::post('auth/register', 'AuthController@register'); // 账号注册
Route::post('auth/regCaptcha', 'AuthController@regCaptcha'); // 注册验证码
Route::post('auth/captcha', 'AuthController@regCaptcha'); // 验证码
Route::post('auth/login', 'AuthController@login'); // 账号登录
Route::post('auth/logout', 'AuthController@logout'); // 账号登出
Route::post('auth/reset', 'AuthController@reset'); // 账号密码重置
Route::post('auth/profile', 'AuthController@profile'); // 账号修改
Route::get('auth/info', 'AuthController@info'); // 用户信息

// 用户模块--地址
Route::get('address/list', 'AddressController@list'); // 收货地址列表
Route::get('address/detail', 'AddressController@detail'); // 收货地址详情
Route::post('address/save', 'AddressController@save'); // 保存收货地址
Route::post('address/delete', 'AddressController@delete'); // 删除收货地址

// 商品模块--分类
Route::get('catalog/index', 'CategoryController@index'); // 分类目录全部分类数据接口
Route::get('catalog/current', 'CategoryController@current'); // 分类目录当前分类数据接口

// 商品模块--品牌
Route::get('brand/list', 'BrandController@list'); // 品牌列表
Route::get('brand/detail', 'BrandController@detail'); // 品牌详情

// 商品模块--商品
Route::get('goods/count', 'GoodsController@count'); // 统计在售商品总数
Route::get('goods/category', 'GoodsController@category'); // 根据分类获取商品列表数据
Route::get('goods/list', 'GoodsController@list'); // 获得商品列表
Route::get('goods/detail', 'GoodsController@detail'); // 获得商品的详情

// 营销模块--优惠券
Route::get('coupon/list', 'CouponController@list'); // 优惠券列表
Route::get('coupon/mylist', 'CouponController@mylist'); // 我的优惠券列表
Route::post('coupon/receive', 'CouponController@receive'); // 领取优惠券
//Route::get('coupon/selectlist', 'CouponController@selectlist'); // 当前订单可用优惠券列表

// 营销模块--团购
Route::get('groupon/list', 'GrouponController@list'); // 团购列表

// 订单模块--购物车
Route::post('cart/add', 'CartController@add'); // 添加商品到购物车
Route::post('cart/fastadd', 'CartController@fastadd'); // 立即购买商品
Route::get('cart/goodscount', 'CartController@goodscount'); // 获取购物车商品件数
Route::get('cart/index', 'CartController@index'); // 获取购物车列表
Route::post('cart/update', 'CartController@update'); // 更新购物车的商品
Route::post('cart/checked', 'CartController@checked'); // 选择或取消选择商品
Route::post('cart/delete', 'CartController@delete'); // 删除购物车的商品
Route::get('cart/checkout', 'CartController@checkout'); // 下单前信息确认

Route::get('home/index', 'HomeController@index'); // 首页数据接口
Route::get('home/redirectShareUrl', 'HomeController@redirectShareUrl')->name('home.redirectShareUrl'); // 获取海报分享页面路径
