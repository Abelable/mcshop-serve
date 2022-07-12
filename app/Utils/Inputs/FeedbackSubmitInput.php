<?php

namespace App\Utils\Inputs;

class FeedbackSubmitInput extends Input
{
    public $mobile;
    public $feedType;
    public $content;
    public $status = 1;
    public $hasPicture = 0;
    public $pic_urls = '';

    public function rules()
    {
        return [
            'mobile' => 'regex:/^1[345789][0-9]{9}$/',
            'feedType' => 'required|string',
            'content'  => 'required|string',
        ];
    }
}
