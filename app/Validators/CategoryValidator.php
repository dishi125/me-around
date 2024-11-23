<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class CategoryValidator extends ModelValidator
{
    protected $languageArray = 'validation.category';

    private $listRules = [
        'category_type_id' => 'exists:category_types,id',
        'language_id' => 'required|exists:post_languages,id',
    ];  

    private $reportListRules = [
        'category_id' => 'exists:category,id',
        'language_id' => 'required|exists:post_languages,id',
    ];    

    public function validateList($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->listRules);
    }
    public function validateReportList($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->reportListRules);
    }
    
}
