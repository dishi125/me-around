<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class PostValidator extends ModelValidator
{
    protected $languageArray = 'validation.post';

    private $storeRules = [
        'hospital_id' => 'required',
        'title' => 'required',
        'sub_title' => 'required',
        'from_date' => 'required|date|date_format:Y-m-d|after:yesterday',
        'to_date' => 'required|date|date_format:Y-m-d|after:from_date',
        'final_price' => 'required',
        'currency_id' => 'required|exists:currency,id',
        'discount_percentage' => 'required',
        'category_id' => 'required',
        'is_discount' => 'required|boolean',
        'thumbnail' => 'required|image|mimes:jpeg,jpg,gif,svg',
    ];    

    private $updateRules = [
        'title' => 'required',
        'sub_title' => 'required',
        'from_date' => 'required|date|date_format:Y-m-d',
        'to_date' => 'required|date|date_format:Y-m-d|after:from_date',
        'final_price' => 'required',
        'currency_id' => 'required|exists:currency,id',
        'discount_percentage' => 'required',
        'category_id' => 'required',
        'is_discount' => 'required|boolean',
        'thumbnail' => 'image|mimes:jpeg,jpg,gif,svg',
    ]; 

    public function validateStore($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->storeRules);
    }
    public function validateUpdate($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->updateRules);
    }
    
}
