<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class DoctorValidator extends ModelValidator
{
    protected $languageArray = 'validation.doctor';

    private $storeRules = [
        'name' => 'required',
        'gender' => 'required',
        'specialty' => 'required',
        'avatar' => 'required|image|mimes:jpeg,jpg,gif,svg',
        'hospital_id' => 'required',
    ];   

    private $updateRules = [
        'name' => 'required',
        'gender' => 'required',
        'specialty' => 'required',
        'avatar' => 'image|mimes:jpeg,jpg,gif,svg',
    ];   

    private $listRules = [
        'hospital_id' => 'required|exists:hospitals,id',
    ];    

    public function validateStore($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->storeRules);
    }
    public function validateUpdate($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->updateRules);
    }
    public function validateList($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->listRules);
    }
    
}
