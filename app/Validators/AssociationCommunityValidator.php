<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class AssociationCommunityValidator extends ModelValidator
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
        'category_id' => 'required',
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

    private $detailRules = [
        'latitude' => 'required',
        'longitude' => 'required'
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

    public function validateDetail($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->detailRules);
    }
    
}
