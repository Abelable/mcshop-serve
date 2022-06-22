<?php

namespace App\Http\Controllers\Wx;

class HomeController extends WxController
{
    protected $except = ['index'];

    public function index()
    {
        return response()->json('123');
    }
}
