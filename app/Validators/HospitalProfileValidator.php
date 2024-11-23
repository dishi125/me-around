<?php

namespace App\Validators;

use Illuminate\Validation\Rule;
use App\Models\Status;

class HospitalProfileValidator extends ModelValidator
{
    protected $languageArray = 'validation.hospital-profile';

    private $updateRules = [
        'main_name' => 'required',
        'description' => 'required',
        'business_license_number' => 'required',
    ];  
    
    private $getPosts = [
        'latitude' => 'required',
        'longitude' => 'required',
    ];  
    
    private $statusRules = [
        'hospital_id' => 'required|exists:hospitals,id',
    ];   



    public function validateUpdate($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->updateRules);
    }

    public function validateGetPost($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->getPosts);
    }

    public function validateGetStatus($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->statusRules);
    }

    public function validateStatusChange($inputs)
    {
        $this->statusRules['status_id'] = ['required', Rule::in([Status::ACTIVE,Status::INACTIVE,Status::HIDDEN,Status::UNHIDE])];
        return parent::validateLaravelRules($inputs, $this->statusRules);
    }
    
}
