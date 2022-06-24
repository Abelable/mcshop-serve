<?php

namespace App\Services;

use App\Enums\Constant;
use App\Input\PageInput;
use App\Models\Comment;
use App\Models\User\User;
use App\Services\User\UserService;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class CommentService extends BaseService
{
    public function getCommentWithUserInfo(int $goodsId, $page = 1, $limit = 2)
    {
        $input = new PageInput();
        $input->page = $page;
        $input->limit = $limit;

        $comments = $this->getGoodsComments($goodsId, $input);

        $userIds = Arr::pluck($comments->items(), 'user_id');
        $userIds = array_unique($userIds);
        $users = UserService::getInstance()->getUsers($userIds)->keyBy('id');

        $data = collect($comments->items())->map(function (Comment $comment) use ($users) {
            /** @var User $user */
            $user = $users->get($comment->user_id);

            // $comment = $comment->toArray(); // 处理时间格式问题
            // $comment['picList'] = $comment['picUrls'];
            // $comment = Arr::only($comment, ['id', 'addTime', 'content', 'adminContent', 'picList']);
            // $comment['nickname'] = $user->nickname ?? '';
            // $comment['avatar'] = $user->avatar ?? '';
            // return $comment;

            return [
                'id' => $comment->id,
                'addTime' => Carbon::instance($comment->add_time)->toDateTimeString(),
                'content' => $comment->content,
                'adminContent' => $comment->admin_content,
                'picList' => $comment->pic_urls,
                'nickname' => $user->nickname ?? '',
                'avatar' => $user->avatar ?? ''
            ];
        });

        return [
            'count' => $comments->total(),
            'data' => $data
        ];
    }

    public function getGoodsComments(int $goodsId, PageInput $input, $columns = ['*'])
    {
        return Comment::query()
            ->where('value_id', $goodsId)
            ->where('type', Constant::COMMENT_TYPE_GOODS)
            ->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }
}
