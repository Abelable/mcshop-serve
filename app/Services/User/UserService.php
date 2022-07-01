<?php

namespace App\Services\User;

use App\Exceptions\BusinessException;
use App\Models\User\User;
use App\Notifications\VerificationCode;
use App\Services\BaseService;
use App\Utils\CodeResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Leonis\Notifications\EasySms\Channels\EasySmsChannel;
use Overtrue\EasySms\PhoneNumber;

class UserService extends BaseService
{
    public function getUsers(array $userIds)
    {
        if (empty($userIds)) {
            return collect([]);
        }
        return User::query()->whereIn('id', $userIds)->get();
    }

    public function getUserById($id)
    {
        return User::query()->find($id);
    }

    public function getByUserName($username)
    {
        return User::query()->where('username', $username)->first();
    }

    public function getByMobile($mobile)
    {
        return User::query()->where('mobile', $mobile)->first();
    }

    public function checkCaptchaSendCount($mobile, $count_limit)
    {
        $key = 'captcha_count_' . $mobile;
        if (Cache::has($key)) {
            $count = Cache::increment($key, 1);
            if ($count > $count_limit) {
                return false;
            }
        } else {
            Cache::put($key, 1, Carbon::tomorrow()->diffInSeconds(now()));
        }
        return true;
    }

    public function setCaptcha($mobile)
    {
        $code = random_int(100000, 999999);
        Cache::put('captcha_' . $mobile, $code, 600);
        return $code;
    }

    public function sendCaptchaMsg($mobile, $code)
    {
        if (!app()->environment('production')) {
            return;
        }
        Notification::route(EasySmsChannel::class, new PhoneNumber($mobile, 86))
            ->notify(new VerificationCode($code, ''));
    }

    public function checkCaptcha($mobile, $code)
    {
        if (!app()->environment('production')) {
            return;
        }
        $key = 'captcha_' . $mobile;
        $isPass = $code == Cache::get($key);
        if ($isPass) {
            Cache::forget($key);
        } else {
            throw new BusinessException(CodeResponse::AUTH_CAPTCHA_UNMATCH);
        }
    }
}
