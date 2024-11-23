<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class ShopPriceValidator extends ModelValidator
{
    protected $languageArray = 'validation.shop-price';

    private $storeRules = [        
        'item_category_id' => 'required',
        'name' => 'required',
        'price' => 'required',
        //'discounted_price' => 'required',
    ];
    private $storeItemCategoryRules = [        
        'shop_id' => 'required',
        'name' => 'required'
    ];

    private $updateRules = [
        'name' => 'required',
        'price' => 'required',
        //'discounted_price' => 'required',
    ];
    private $updateItemCategoryRules = [
        'name' => 'required',
    ];
    private $getDiscountConditionRules = [
        'language_id' => 'required|exists:post_languages,id',
        'shop_id' => 'required|exists:shops,id',
    ];
    
    private $selectDiscountConditionRules = [
        'shop_id' => 'required|exists:shops,id',
        'discount_condition_id' => 'required'
    ];


    public function validateStore($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->storeRules);
    }
    public function validateItemCategoryStore($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->storeItemCategoryRules);
    }

    public function validateUpdate($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->updateRules);
    }
    public function validateItemsCategoryUpdate($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->updateItemCategoryRules);
    }

    public function validateGetDiscountCondition($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->getDiscountConditionRules);
    }
    
    public function validateSelectDiscountCondition($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->selectDiscountConditionRules);
    }

    
}
