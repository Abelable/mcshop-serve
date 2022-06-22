<?php

namespace App\Input;

class AuthRegisterInput extends Input
{
    public $username;
    public $password;
    public $mobile;
    public $code;

    public function rules()
    {
        return [
            'username' => 'required|string',
            'password' => 'required|string',
            'mobile' => 'required|regex:/^1[345789][0-9]{9}$/',
            'code' => 'required|string'
        ];
    }
}
