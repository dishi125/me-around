<?php

namespace App\Validators;

use Illuminate\Validation\Rule;
use App\Models\EntityTypes;

class ReloadCoinValidator extends ModelValidator
{
    protected $languageArray = 'validation.reload-coin';

    private $getRules = [
        'currency_id' => 'required|exists:reload_coin_currency,id',
    ]; 

    private $storeRules = [
        'currency_id' => 'required|exists:reload_coin_currency,id',
        'sender_name' => 'required',
        'coin_amount' => 'required',
        'supply_price' => 'required',
        'vat_amount' => 'required',
        'total_amount' => 'required',
    ];   
      
    public function validateGetData($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->getRules);
    }   

    public function validateStore($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->storeRules);
    }    
}
