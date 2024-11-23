<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class ReportValidator extends ModelValidator
{
    protected $languageArray = 'validation.report';

    private $storeRules = [
        'report_type_id' => 'required|exists:report_types,id',
        //'report_category_id' => 'required|exists:category,id',
        'report_category_id' => 'required',
        'entity_id' => 'required',
    ];    

    public function validateStore($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->storeRules);
    }
    
}
