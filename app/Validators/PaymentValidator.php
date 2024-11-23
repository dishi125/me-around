<?php

namespace App\Validators;

use Illuminate\Validation\Rule;
use App\Models\SavedHistoryTypes;
use App\Models\EntityTypes;
use App\Models\Status;

class PaymentValidator extends ModelValidator
{
    protected $languageArray = 'validation.payment';

    private $paypalRules = [
        'payment_method_nonce' => 'required',
        'amount' => 'required',
    ];

    public function validatePaypal($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->paypalRules);
    }
    
}
