<?php

namespace App\Validators;

use Illuminate\Validation\Rule;
use App\Models\EntityTypes;

class MessageValidator extends ModelValidator
{
    protected $languageArray = 'validation.post';

    private $deleteRules = [
        'entity_id' => 'required',
        'from_user_id' => 'required',
        'to_user_id' => 'required',
    ]; 

    private $initiateRules = [
        'entity_id' => 'required',
        'user_id' => 'required',
    ];  

    private $checkUserRules = [
        'user_id' => 'required|exists:users,id',
    ];   

    private $getInquiryRules = [
        'booking_status_id' => 'required|exists:request_booking_status,id',
    ]; 

    public function validateDelete($inputs)
    {
        $this->deleteRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        return parent::validateLaravelRules($inputs, $this->deleteRules);
    }    
    public function validateIntiate($inputs)
    {
        $this->deleteRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        return parent::validateLaravelRules($inputs, $this->initiateRules);
    }    
    public function validateCheckUser($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->checkUserRules);
    }   
    
    public function validateGetInquiryList($inputs)
    {
        $this->getInquiryRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        return parent::validateLaravelRules($inputs, $this->getInquiryRules);
    }  
}
