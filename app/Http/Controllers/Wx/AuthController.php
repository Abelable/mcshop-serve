<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Input\AuthRegisterInput;
use App\Models\User\User;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AuthController extends WxController
{
    protected $only = ['info', 'profile'];

    public function register(Request $request)
    {
        /** @var AuthRegisterInput $input */
        $input = AuthRegisterInput::new();

        $user = UserService::getInstance()->getByUserName($input->username);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_NAME_REGISTERED);
        }

        $user = UserService::getInstance()->getByMobile($input->mobile);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }

        UserService::getInstance()->checkCaptcha($input->mobile, $input->code);

        $avatarUrl = "https://yanxuan.nosdn.127.net/80841d741d7fa3073e0ae27bf487339f.jpg?imageView&quality=90&thumbnail=64x64";
        $user = User::new();
        $user->username = $input->username;
        $user->password = Hash::make($input->password);
        $user->mobile = $input->mobile;
        $user->avatar = $avatarUrl;
        $user->nickname = $input->username;
        $user->last_login_time = Carbon::now()->toDateTimeString();
        $user->last_login_ip = $request->getClientIp();
        $user->add_time = Carbon::now()->toDateTimeString();
        $user->update_time = Carbon::now()->toDateTimeString();
        $user->save();

        $token = Auth::guard('wx')->login($user);

        return $this->success([
            'token' => $token,
            'userInfo' => [
                'nickName' => $input->username,
                'avatarUrl' => $avatarUrl
            ]
        ]);
    }

    public function regCaptcha()
    {
        $mobile = $this->verifyMobile();

        $user = UserService::getInstance()->getByMobile($mobile);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }

        // Cache::add：如果缓存存在，再添加会返回错误；利用这个特性实现60s内只能发送一次验证码的限制
        $lock = Cache::add('captcha_lock' . $mobile, 1, 60);
        if (!$lock) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY);
        }

        $isPass = UserService::getInstance()->checkCaptchaSendCount($mobile, 10);
        if (!$isPass) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY, '验证码每天发送不能超过10次');
        }

        $code = UserService::getInstance()->setCaptcha($mobile);
        UserService::getInstance()->sendCaptchaMsg($mobile, $code);

        return $this->success();
    }

    public function login(Request $request)
    {
        $username = $this->verifyRequiredString('username');
        $password = $this->verifyRequiredString('password');

        $user = UserService::getInstance()->getByUserName($username);
        if (is_null($user)) {
            return $this->fail(CodeResponse::AUTH_INVALID_ACCOUNT);
        }

        $isPass = Hash::check($password, $user->getAuthPassword());
        if (!$isPass) {
            return $this->fail(CodeResponse::AUTH_INVALID_ACCOUNT, '密码错误');
        }

        // 更新登录信息
        $user->last_login_time = Carbon::now()->toDateTimeString();
        $user->last_login_ip = $request->getClientIp();
        if (!$user->save()) {
            return $this->fail(CodeResponse::UPDATED_FAIL);
        }

        $token = Auth::guard('wx')->login($user);

        return $this->success([
            'token' => $token,
            'userInfo' => [
                'nickname' => $username,
                'avatarUrl' => $user->avatar
            ]
        ]);
    }

    public function logout()
    {
        Auth::guard('wx')->logout();
        return $this->success();
    }

    public function reset()
    {
        $mobile = $this->verifyMobile();
        $code = $this->verifyRequiredString('code');
        $password = $this->verifyRequiredString('password');

        UserService::getInstance()->checkCaptcha($mobile, $code);

        $user = UserService::getInstance()->getByMobile($mobile);
        if (is_null($user)) {
            return $this->fail(CodeResponse::AUTH_INVALID_ACCOUNT);
        }

        $user->password = Hash::make($password);
        $ret = $user->save();

        return $this->failOrSuccess($ret, CodeResponse::UPDATED_FAIL);
    }

    public function profile()
    {
        $nickname = $this->verifyString('nickname');
        $avatar = $this->verifyString('avatar');
        $gender = $this->verifyInteger('gender');

        $user = $this->user();
        if (!empty($nickname)) {
            $user->nickname = $nickname;
        }
        if (!empty($avatar)) {
            $user->avatar = $avatar;
        }
        if (!empty($gender)) {
            $user->gender = $gender;
        }
        $ret = $user->save();

        return $this->failOrSuccess($ret, CodeResponse::UPDATED_FAIL);
    }

    public function info()
    {
        $user = $this->user();
        return $this->success([
            'nickName' => $user->nickname,
            'avatar' => $user->avatar,
            'gender' => $user->gender,
            'mobile' => $user->mobile
        ]);
    }
}
