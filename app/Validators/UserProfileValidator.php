<?php

namespace App\Validators;

use Illuminate\Validation\Rule;
use App\Models\SavedHistoryTypes;
use App\Models\EntityTypes;
use App\Models\Status;
use App\Models\UserDetail;

class UserProfileValidator extends ModelValidator
{
    protected $languageArray = 'validation.user-profile';

    private $updateRules = [
        'avatar' => 'image|mimes:jpeg,jpg,gif,svg,png',
        'is_character_as_profile' => 'required'
    ];

    private $storeUpdateRules = [        
        'name' => 'required',
        'phone' => 'required',
        'phone_code' => 'required',
        'gender' => 'required',
    ];
    private $changePasswordRules = [        
        'old_password' => 'required|min:6',
        'password' => 'required|min:6',
    ];

    private $addHistoryRules = [
        'entity_id' => 'required',
    ];

    private $scheduleRules = [
        'date' => 'required|date_format:Y-m-d',
    ];

    private $planRules = [
        'entity_type_id' => 'required',
    ];

    private $planUpdateRules = [
        'package_plan_id' => 'required|exists:package_plans,id',
    ];

    private $statusChangeRules = [
        'entity_id' => 'required',
    ];

    private $languageChangeRules = [
        'language_id' => 'required',
    ];

    private $locationChangeRules = [
        'address' => 'required',
        'latitude' => 'required',
        'longitude' => 'required',
        'city_id' => 'required',
        'state_id' => 'required',
        'country_id' => 'required',
    ];

    private $popupRules = [
        'latitude' => 'required',
        'longitude' => 'required',
    ];

    private $popupHideRules = [
        'banner_image_id' => 'required|exists:banner_images,id,deleted_at,NULL',
    ];

    private $addScheduleRules = [
        'entity_id' => 'required',
        'user_name' => 'required',
        'latitude' => 'required',
        'longitude' => 'required',
        'booking_date' => 'required|date_format:Y-m-d H:i:s|after:yesterday',     
    ];

    private $snsRules = [
        'sns_link' => 'required|url',
    ]; 

    private $searchRules = [];

    public function validateAddSchedule($inputs)
    {
        $this->addScheduleRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        return parent::validateLaravelRules($inputs, $this->addScheduleRules);
    }

    public function validateImage($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->updateRules);
    }

    public function validateUpdate($inputs,$language_id)
    {
        if($language_id < 4){
            $this->languageArray = 'validation.user-profile-'.$language_id;
        }
        return parent::validateLaravelRules($inputs, $this->storeUpdateRules);
    }
    public function validateChangePassword($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->changePasswordRules);
    }

    public function validateAddHistory($inputs)
    {
        $this->storeRules['type'] = ['required', Rule::in([SavedHistoryTypes::HOSPITAL, SavedHistoryTypes::SHOP,SavedHistoryTypes::COMMUNITY, SavedHistoryTypes::REVIEWS])];
        return parent::validateLaravelRules($inputs, $this->addHistoryRules);
    }
    
    public function validateSchedule($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->scheduleRules);
    }

    public function validatePlan($inputs)
    {
        $this->planRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        return parent::validateLaravelRules($inputs, $this->planRules);
    }

    public function validateUpdatePlan($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->planUpdateRules);
    }

    public function validateStatusChange($inputs)
    {
        $this->planRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        $this->planRules['status_id'] = ['required', Rule::in([Status::ACTIVE, Status::INACTIVE])];
        return parent::validateLaravelRules($inputs, $this->statusChangeRules);
    }

    public function validateSearchHistory($inputs)
    {
        $this->searchRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP, EntityTypes::COMMUNITY])];
        return parent::validateLaravelRules($inputs, $this->searchRules);
    }

    public function validateChangeLanguage($inputs)
    {
        $this->languageChangeRules['language_id'] = ['required', Rule::in([1,2,3,4,5,6,7,8])];
        return parent::validateLaravelRules($inputs, $this->languageChangeRules);
    }

    public function validateChangeLocation($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->locationChangeRules);
    }

    public function validateChangeAddress($inputs)
    {
        $this->locationChangeRules['entity_type_id'] = ['required', Rule::in([EntityTypes::HOSPITAL, EntityTypes::SHOP])];
        return parent::validateLaravelRules($inputs, $this->locationChangeRules);
    }
    public function validatePopup($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->popupRules);
    }

    public function validatePopupHide($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->popupHideRules);
    }

    public function validateConnectSNSStatus($inputs)
    {
        $this->snsRules['sns_type'] = ['required', Rule::in([UserDetail::INSTAGRAM,UserDetail::FACEBOOK,UserDetail::WEIBO])];
        return parent::validateLaravelRules($inputs, $this->snsRules);
    }    
}
