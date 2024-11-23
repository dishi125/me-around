<?php

namespace App\Validators;

use Illuminate\Validation\Rule;
use App\Models\EntityTypes;

class RequestServiceValidator extends ModelValidator
{
    protected $languageArray = 'validation.request-service';

    private $storeRules = [
        'latitude' => 'required',
        'longitude' => 'required',
        'entity_id' => 'required',
        'user_id' => 'required|exists:users,id',
        'booking_date' => 'required|date_format:Y-m-d H:i:s|after:yesterday',     
    ];

    private $changeDateRules = [
        'booking_date' => 'required|date_format:Y-m-d H:i:s|after:yesterday',  
        'latitude' => 'required',
        'longitude' => 'required',   
    ]; 
      
    private $completeMemoRules = [
        'revenue' => 'required',
        'comment' => 'required',     
    ];

    private $revenueYearRules = [
        'year' => 'required|digits:4|integer',       
    ];    
    private $revenueMonthRules = [
        'month' => 'required|integer', 
        'year' => 'required|digits:4|integer',      
    ];    
    private $revenueUserRules = [
        'user_id' => 'required|exists:users,id', 
        'entity_id' => 'required||integer',      
    ];    
    private $cancelServiceRules = [
        'user_cancelled' => 'required',       
        // 'reason' => 'required',       
    ];
    
    private $creditDeductRules = [
        'entity_id' => 'required',
        'from_user_id' => 'required|exists:users,id',
        'to_user_id' => 'required|exists:users,id',
    ];

    public function validateStore($inputs)
    {
        $this->storeRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        return parent::validateLaravelRules($inputs, $this->storeRules);
    }
    public function validateCreditDeduct($inputs)
    {
        $this->storeRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        return parent::validateLaravelRules($inputs, $this->creditDeductRules);
    }
    public function validateCancel($inputs)
    {
        $this->cancelServiceRules['user_cancelled'] = ['required', Rule::in([0,1])];
        return parent::validateLaravelRules($inputs, $this->cancelServiceRules);
    }

    public function validateCompleteMemo($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->completeMemoRules);
    }

    public function validateYearRevenue($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->revenueYearRules);
    }
    public function validateMonthRevenue($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->revenueMonthRules);
    }
    public function validateUserRevenue($inputs)
    {
        $this->revenueUserRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        return parent::validateLaravelRules($inputs, $this->revenueUserRules);
    }
    public function validateChangeDate($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->changeDateRules);
    }
    
}
