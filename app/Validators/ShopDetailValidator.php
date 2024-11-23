<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class ShopDetailValidator extends ModelValidator
{
    protected $languageArray = 'validation.shopdetail';

    private $saveShopDetail = [
        'shop_id' => 'required',
    ];

    private $shopUsageDetail = [
        'shop_id' => 'required',
        'title' => 'required',
        'recycle_type' => 'required|in:single,recycle',
        'recycle_option' => 'exclude_if:recycle_type,single|required',
    ];

    private $shopLanguageDetail = [
        'shop_id' => 'required',
        'details' => 'required',
        'details.*.language_id' => 'required',
        'details.*.value' => 'required',        
    ];

    public function validateSaveShopDetail($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->saveShopDetail);
    }

    public function validateShopUsageDetail($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->shopUsageDetail);
    }

    public function validateShopLanguageDetail($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->shopLanguageDetail);
    }
}
