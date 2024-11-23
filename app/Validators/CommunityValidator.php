<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class CommunityValidator extends ModelValidator
{
    protected $languageArray = 'validation.community';

    private $storeRules = [
        'title' => 'required',
        'description' => 'required',
        'category_id' => 'required',
        // 'images' => 'required',
        'latitude' => 'required',
        'longitude' => 'required',
    ];  

    private $getRules = [
        'category_id' => 'required|exists:category,id',
        'sub_category_id' => 'exists:category,id',
        'latitude' => 'required',
        'longitude' => 'required',
    ];    
    private $commentRules = [
        'comment' => 'required',
    ];  
    
    private $searchRules = [
        'latitude' => 'required',
        'longitude' => 'required',
        'keyword' => 'required',
    ]; 

    public function validateStore($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->storeRules);
    }
    public function validateGetCommunity($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->getRules);
    }
    public function validateCommunityComment($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->commentRules);
    }

    public function validateSearch($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->searchRules);
    }
    
}
