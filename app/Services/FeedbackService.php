<?php

namespace App\Services;

use App\Models\Feedback;
use App\Services\User\UserService;
use App\Utils\Inputs\FeedbackSubmitInput;
use Illuminate\Support\Carbon;

class FeedbackService extends BaseService
{
    public function add(FeedbackSubmitInput $input, int $userId)
    {
        $user = UserService::getInstance()->getUserById($userId);

        $feedback = Feedback::new();
        $feedback->status = $input->status;
        $feedback->content = $input->content;
        $feedback->mobile = $input->mobile;
        $feedback->add_time = Carbon::now()->toDateTimeString();
        $feedback->user_id = $userId;
        $feedback->username = $user->username;
        $feedback->feed_type = $input->feedType;
        return $feedback->save();
    }
}
