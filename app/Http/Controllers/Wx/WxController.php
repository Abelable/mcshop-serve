<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\VerifyRequestInput;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class WxController extends Controller
{
    use VerifyRequestInput;

    protected $only;
    protected $except;

    public function __construct()
    {
        $options = [];
        if (!is_null($this->only)) {
            $options['only'] = $this->only;
        }
        if (!is_null($this->except)) {
            $options['except'] = $this->except;
        }
        $this->middleware('auth:wx', $options);
    }

    public function isLogin()
    {
        return !is_null($this->user());
    }

    public function userId()
    {
        return $this->user()->getAuthIdentifier();
    }

    /**
     * @return User
     */
    public function user()
    {
        return Auth::guard('wx')->user();
    }

    protected function successPaginate($page)
    {
        return $this->success($this->paginate($page));
    }

    protected function paginate($page, $list = null)
    {
        if ($page instanceof LengthAwarePaginator) {
            $total = $page->total();
            return [
                'total' => $total,
                'page' => $total == 0 ? 0 : $page->currentPage(),
                'limit' => $page->perPage(),
                'pages' => $total == 0 ? 0 : $page->lastPage(),
                'list' => $list ?? $page->items()
            ];
        }

        if ($page instanceof Collection) {
            $page = $page->toArray();
        }

        if (!is_array($page)) {
            return $page;
        }

        $total = count($page);
        return [
            'total' => $total,
            'page' => $total == 0 ? 0 : 1,
            'limit' => $total,
            'pages' => $total == 0 ? 0 : 1,
            'list' => $page
        ];
    }

    protected function failOrSuccess(bool $isSuccess, $codeResponse = CodeResponse::FAIL, $data = null, $info = '')
    {
        if ($isSuccess) {
            return $this->success($data);
        }
        return $this->fail($codeResponse, $info);
    }

    protected function success($data = null, $info = '')
    {
        return $this->codeReturn(CodeResponse::SUCCESS, $data, $info);
    }

    protected function badArgument()
    {
        return $this->fail(CodeResponse::PARAM_ILLEGAL);
    }

    protected function badArgumentValue()
    {
        return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
    }

    protected function fail(array $codeResponse, $info = '', $data = null)
    {
        return $this->codeReturn($codeResponse, $data, $info);
    }

    private function codeReturn(array $codeResponse, $data = null, $info = '')
    {
        list($errno, $errmsg) = $codeResponse;
        $res = ['errno' => $errno];
        if (is_array($data)) {
            $data = array_filter($data, function ($item) {
               return $item !== null;
            });
            $res['data'] = $data;
        } elseif (!is_null($data)) {
            $res['data'] = $data;
        }
        $res['errmsg'] = $info ?: $errmsg;
        return response()->json($res);
    }
}
