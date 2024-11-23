<?php

namespace App\Validators;

use App\Models\EntityTypes;
use App\Models\RequestFormStatus;
use App\Models\Status;
use Illuminate\Validation\Rule;

class RequestFormValidator extends ModelValidator
{
    protected $languageArray = 'validation.request-form';

    private $storeRules = [
        'name' => 'required',
        'category_id' => 'required_if:entity_type_id,1|exists:category,id',
        'address' => 'required',
        'latitude' => 'required',
        'longitude' => 'required',
        'business_license_number' => 'nullable',
        'recommend_code' => 'nullable',
        'city_id' => 'nullable',
        'state_id' => 'nullable',
        'country_id' => 'nullable',
    ];

    public function validateStore($inputs)
    {
        $language_id = $inputs['language_id'] ?? 4;
        if($language_id < 4){
            $this->languageArray = 'validation.request-form-'.$language_id;
        }

        $this->storeRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
      
        if (isset($inputs['entity_type_id']) && $inputs['entity_type_id'] == EntityTypes::HOSPITAL) {
            $this->storeRules['user_id'] = ['required',Rule::unique('request_forms','user_id')->where(function ($query) use ($inputs){
                $query->where('request_forms.user_id', $inputs['user_id'])
                ->where(function($q) {
                    $q->where('request_count','>=',3)
                    ->orWhere('request_status_id','=',RequestFormStatus::PENDING)
                    ->orWhere('request_status_id','=',RequestFormStatus::CONFIRM);	
                });
            })];
            
            $this->storeRules['email'] = ['nullable', 'email', Rule::unique('request_forms', 'email')->where(function ($query) use ($inputs){
                $query->where('request_forms.user_id', $inputs['user_id'])
                ->where(function($q) {
                    $q->where('request_count','>=',3)
                    ->orWhere('request_status_id','=',RequestFormStatus::PENDING)
                    ->orWhere('request_status_id','=',RequestFormStatus::CONFIRM);	
                });
            })];
            $this->storeRules['business_licence'] = 'nullable|mimes:jpeg,jpg,bmp,png';
            $this->storeRules['interior_photo'] = 'nullable|mimes:jpeg,jpg,bmp,png';
        } else if (isset($inputs['entity_type_id']) && $inputs['entity_type_id'] == EntityTypes::SHOP) {
            $this->storeRules['user_id'] = ['required', Rule::unique('request_forms', 'user_id')->where(function ($query) use ($inputs) {
                $query->where('request_forms.user_id', $inputs['user_id'])
                ->where(function($q) {
                    $q->where('request_count','>=',3)
                    ->orWhere('request_status_id','=',RequestFormStatus::PENDING)
                    ->orWhere('request_status_id','=',RequestFormStatus::CONFIRM);	
                });
            })];
            $this->storeRules['email'] = ['nullable', 'email', Rule::unique('request_forms', 'email')->where(function ($query) use ($inputs) {
                $query->where('request_forms.user_id', $inputs['user_id'])
                ->where(function($q) {
                    $q->where('request_count','>=',3)
                    ->orWhere('request_status_id','=',RequestFormStatus::PENDING)
                    ->orWhere('request_status_id','=',RequestFormStatus::CONFIRM);	
                });
            })];
            $this->storeRules['business_licence'] = 'nullable|mimes:jpeg,jpg,bmp,png';
            $this->storeRules['best_portfolio'] = 'nullable|mimes:jpeg,jpg,bmp,png';
            $this->storeRules['identification_card'] = 'nullable|mimes:jpeg,jpg,bmp,png';
        }

        return parent::validateLaravelRules($inputs, $this->storeRules);
    }
    public function validateCustomStore($inputs)
    {
        $this->storeRules['entity_type_id'] = ['required', Rule::in([EntityTypes::SHOP])];

        if (isset($inputs['entity_type_id']) && $inputs['entity_type_id'] == EntityTypes::SHOP) {
            
            $this->storeRules['business_licence'] = 'required|mimes:jpeg,jpg,bmp,png';
            $this->storeRules['best_portfolio'] = 'required|mimes:jpeg,jpg,bmp,png';
            $this->storeRules['identification_card'] = 'required|mimes:jpeg,jpg,bmp,png';
        }

        return parent::validateLaravelRules($inputs, $this->storeRules);
    }
}
