<?php

namespace App\Http\Controllers\Wx;

use App\Services\FeedbackService;
use App\Utils\Inputs\FeedbackSubmitInput;

class FeedbackController extends WxController
{
    public function submit()
    {
        $input = FeedbackSubmitInput::new();
        FeedbackService::getInstance()->add($input, $this->userId());
        return $this->success();
    }
}
