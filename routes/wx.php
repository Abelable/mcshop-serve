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
Route::get('address/list', 'AddressController@list');
Route::get('address/detail', 'AddressController@detail');
Route::post('address/save', 'AddressController@save');
Route::post('address/delete', 'AddressController@delete');

Route::get('home/index', 'HomeController@index'); // 首页数据接口
