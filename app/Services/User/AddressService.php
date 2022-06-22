<?php

namespace App\Services\User;

use App\Input\AddressSaveInput;
use App\Models\User\Address;
use App\Services\BaseService;

class AddressService extends BaseService
{
    public function getAddressList($userId)
    {
        return Address::query()->where('user_id', $userId)->get();
    }

    public function saveAddress($userId, AddressSaveInput $input)
    {
        if (!is_null($input->id)) {
            $address = $this->getAddress($userId, $input->id);
        } else {
            $address = Address::new();
            $address->user_id = $userId;
        }

        if ($input->isDefault) {
            $this->resetDefaultAddress($userId);
        }

        $address->name = $input->name;
        $address->province = $input->province;
        $address->city = $input->city;
        $address->county = $input->county;
        $address->address_detail = $input->addressDetail;
        $address->area_code = $input->areaCode;
        $address->postal_code = $input->postalCode;
        $address->tel = $input->tel;
        $address->is_default = $input->isDefault;
        $address->save();

        return $address;
    }

    public function resetDefaultAddress($userId)
    {
        if (!Address::query()->where('user_id', $userId)->update(['is_default' => 0])){
            $this->throwUpdateFail();
        }
    }

    public function delete($userId, $addressId)
    {
        $address = $this->getAddress($userId, $addressId);
        if (is_null($address)) {
            $this->throwBadArgumentValue();
        }
        return $address->delete();
    }

    public function getAddressOrDefault($userId, $addressId = null)
    {
        if (is_null($addressId)) {
            $address = $this->getDefaultAddress($userId);
        } else {
            $address = $this->getAddress($userId, $addressId);
            if (is_null($address)) {
                $this->throwBadArgumentValue();
            }
        }
        return $address;
    }

    public function getAddress($userId, $addressId)
    {
        return Address::query()->where('user_id', $userId)->where('id', $addressId)->first();
    }

    public function getDefaultAddress($userId)
    {
        return Address::query()->where('user_id', $userId)->where('is_default', 1)->first();
    }
}
