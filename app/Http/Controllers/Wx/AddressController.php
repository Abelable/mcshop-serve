<?php

namespace App\Http\Controllers\Wx;

use App\Services\User\AddressService;
use App\Utils\CodeResponse;
use App\Utils\Inputs\AddressSaveInput;

class AddressController extends WxController
{
    public function list()
    {
        $list = AddressService::getInstance()->getAddressList($this->userId());
        return $this->successPaginate($list);
    }

    public function detail()
    {
        $id = $this->verifyId('id', 0);
        $address = AddressService::getInstance()->getAddress($this->userId(), $id);
        if (is_null($address)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }
        return $this->success($address);
    }

    public function save()
    {
        $input = AddressSaveInput::new();
        $address = AddressService::getInstance()->saveAddress($this->userId(), $input);
        return $this->success($address);
    }

    public function delete()
    {
        $id = $this->verifyId('id', 0);
        AddressService::getInstance()->delete($this->userId(), $id);
        return $this->success();
    }
}
