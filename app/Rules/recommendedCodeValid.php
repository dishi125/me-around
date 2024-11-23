<?php

namespace App\Rules;
use App\Models\Manager;

use Illuminate\Contracts\Validation\Rule;

class recommendedCodeValid implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if($value != NULL){
            $recommendeCode = Manager::where('recommended_code',$value)->first();
            return !empty($recommendeCode) ? true : false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid Recommended Code.';
    }
}
