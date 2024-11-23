<?php

namespace App\Validators;

use App\Models\LinkedSocialProfile;
use Illuminate\Validation\Rule;

class UserValidator extends ModelValidator
{
    protected $languageArray = 'validation.user';

    private $storeUpdateRules = [
		'email' => 'required|string|max:255',
		'password' => 'required|string|min:6',
        'name' => 'required',
//        'phone_code' => 'required',
//        'phone' => 'required|numeric',
//        'gender' => 'required|string',
        'device_id' => 'required',
        'device_type_id' => 'required',
        'device_token' => 'required',
        'recommend_code' => 'nullable',
    ];

    private $emailValidateRules = [
		'email' => 'required|string|max:255|unique:users,email,NULL,id,deleted_at,NULL'
    ];

    private $tagRules = [
        'tag' => 'required',
    ];

    private $socialRules = [
       // 'email' => 'required',
        'social_id' => 'required',
        //'social_type' => 'required',
    ];

    private $instaRule = [
        'social_id' => 'required',
        'social_name' => 'required',
        'access_token' => 'required',
       // 'social_type' => 'required',
    ];

    private $userGoogleSignupRules = [
        'email' => 'required|email',
//        'phone_code' => 'required',
//        'phone' => 'required',
        'device_type_id' => 'required',
        'device_id' => 'required',
        'device_token' => 'required',
    ];

    private $userAppleSignupRules = [
//        'email' => 'required|email',
        'device_type_id' => 'required',
        'device_id' => 'required',
        'device_token' => 'required',
    ];

    private $googleLoginRules = [
        'email' => 'required',
    ];

    private $appleLoginRules = [
        'apple_social_id' => 'required',
        'auth_code' => 'required',
    ];

    public function validateDisconnectSocial($inputs){
        $socialDisconnectRules['social_type'] = ['required', Rule::in([LinkedSocialProfile::Facebook,LinkedSocialProfile::Instagram,LinkedSocialProfile::Apple])];
        $socialDisconnectRules['shop_id'] = ['required_if:social_type,'.LinkedSocialProfile::Instagram];
        return parent::validateLaravelRules($inputs, $socialDisconnectRules);
    }

    public function validateSocial($inputs,$language_id){
        $this->socialRules['social_type'] = ['required', Rule::in([LinkedSocialProfile::Facebook,LinkedSocialProfile::Instagram,LinkedSocialProfile::Apple])];
        $this->socialRules['shop_id'] = ['required_if:social_type,'.LinkedSocialProfile::Instagram];

        if($language_id < 4){
            $this->languageArray = 'validation.user-'.$language_id;
        }

        return parent::validateLaravelRules($inputs, $this->socialRules);
    }

    public function validateRegister($inputs,$language_id)
    {
        if($language_id < 4){
            $this->languageArray = 'validation.user-'.$language_id;
        }
        return parent::validateLaravelRules($inputs, $this->emailValidateRules);
    }

    public function validateStore($inputs,$language_id)
    {
        if($language_id < 4){
            $this->languageArray = 'validation.user-'.$language_id;
        }
        return parent::validateLaravelRules($inputs, $this->storeUpdateRules);
    }

    public function validateUpdate($inputs, $user)
    {
        $this->storeUpdateRules['email'] = str_replace('NULL', $user->id, $this->storeUpdateRules['email']);
        $this->storeUpdateRules['phone'] = str_replace('NULL', $user->id, $this->storeUpdateRules['phone']);

        return parent::validateLaravelRules($inputs, $this->storeUpdateRules);
    }

    public function validateTags($inputs,$language_id){
        if($language_id < 4){
            $this->languageArray = 'validation.user-'.$language_id;
        }
        return parent::validateLaravelRules($inputs, $this->tagRules);
    }

    public function validateInstagram($inputs){
        return parent::validateLaravelRules($inputs, $this->instaRule);
    }

    public function validateGoogleSignup($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->userGoogleSignupRules);
    }

    public function validateGoogleLogin($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->googleLoginRules);
    }

    public function validateAppleSignup($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->userAppleSignupRules);
    }

    public function validateAppleLogin($inputs)
    {
        return parent::validateLaravelRules($inputs, $this->appleLoginRules);
    }
}
