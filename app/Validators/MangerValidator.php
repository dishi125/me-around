<?php

namespace App\Validators;

class MangerValidator extends ModelValidator
{
    protected $languageArray = 'validation.manager';

    private $storeRules = [
        'email' => 'required|string|max:255|unique:users,email,NULL,id,deleted_at,NULL',
        'password' => 'required|string|min:6',
        'name' => 'required|string',
        'country' => 'required',
        //'state' => 'required',
        //'city' => 'required',
        'role' => 'required',
        'recommended_code' => 'required|alpha_num|min:1|max:7|unique:managers,recommended_code,NULL,id,deleted_at,NULL',
    ];
    private $updateRules = [
        'name' => 'required|string',
        'country' => 'required',
        'password' => 'nullable|string|min:6',
        //'state' => 'required',
        //'city' => 'required',
    ];

    public function validateStore($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->storeRules);
    }

    public function validateUpdate($inputs, $id)
    {
        $this->updateRules['recommended_code'] = 'required|alpha_num|min:1|max:7|unique:managers,recommended_code,' . $id;
        return parent::validateLaravelRules($inputs, $this->updateRules);
    }
}
