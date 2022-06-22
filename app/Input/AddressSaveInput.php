<?php

namespace App\Input;

class AddressSaveInput extends Input
{
    public $id;
    public $name;
    public $province;
    public $city;
    public $county;
    public $addressDetail;
    public $areaCode = '';
    public $postalCode = '';
    public $tel;
    public $isDefault;

    public function rules()
    {
        return [
            'id' => 'integer',
            'name' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'county' => 'required|string',
            'addressDetail' => 'required|string',
            'isDefault' => 'bool',
            'tel' => 'regex:/^1[345789][0-9]{9}$/',
        ];
    }
}
