<?php

namespace App\Validators;

use Illuminate\Validation\Rule;

class InstaCategoryValidator extends ModelValidator
{
//    protected $languageArray = 'validation.category';

    private $listRules = [
        'language_id' => 'required|exists:post_languages,id',
    ];

    private $optionListRules = [
        'language_id' => 'required|exists:post_languages,id',
        'category_id' => 'required|exists:instagram_categories,id',
    ];

    private $subscribeRules = [
        'instagram_category_id' => 'required|exists:instagram_categories,id',
        'option_id' => 'required|exists:instagram_category_options,id',
    ];

    public function validateList($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->listRules);
    }

    public function validateOptionList($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->optionListRules);
    }

    public function validateSubscribe($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->subscribeRules);
    }

}
