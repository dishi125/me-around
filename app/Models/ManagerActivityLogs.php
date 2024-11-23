<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManagerActivityLogs extends Model
{
    protected $table = 'manager_activity_logs';

    const SNS_REWARD = 1; // entity_id = UserID
    const SNS_PENALTY = 2; // entity_id = UserID
    const DELETE_ACCOUNT = 3; // entity_id = UserID
    const CHANGE = 4; // entity_id = 
    const EDIT_SETTINGS = 5; // entity_id = configID
    const EDIT_LANGUAGE_SETTINGS = 6; // entity_id = configLanguageID 
    const EDIT_CREDIT_SETTINGS = 7; // entity_id = CreditPlansID
    const EDIT_CATEGORY = 8; // entity_id = categoryID
    const EDIT_LANGUAGE_CATEGORY = 9; // entity_id = categoryLanguageID
    const RELOAD_COIN = 10; // entity_id = userID
    const UPDATE_COIN = 11; // entity_id = userID
    const REJECT_COIN = 12; // entity_id = userID
    const BUSINESS_REQUEST_CONFIRM = 13; // entity_id = userID
    const BUSINESS_REQUEST_REJECT = 14; // entity_id = userID
    const CREATE_MANAGER = 15; // entity_id = userID

    const TYPE_NAME = [
        1 => 'SNS Reward',
        2 => 'SNS Penalty',
        3 => 'Delete Account',
        4 => 'Change',
        5 => 'Edit Settings',
        6 => 'Edit Settings',
        7 => 'Edit Settings',
        8 => 'Edit Category',
        9 => 'Edit Category',
        10 => 'Reload Coin',
        11 => 'Update Coin',
        12 => 'Reject Coin',
        13 => 'Business Request Confirm',
        14 => 'Business Request Reject',
        15 => 'Create Manager',
    ];

    protected $fillable = [
        'activity_type','user_id','entity_id', 'value', 'created_at','updated_at'
    ];

    protected $appends = ['activity_type_name','activity_name'];

    public function getValueAttribute()
    {
        $value = $this->attributes['value'];
        $valueArray = explode("|", $value,2);
        $filterValue = $valueArray[0] ?? $value;

        if (strpos($filterValue, 'uploads') !== false) {
            $filterValue = "<a href='".Storage::disk('s3')->url($filterValue)."' target='_blank'> See </a>";
        }

        return $this->attributes['activity_type_name'] = $filterValue;
    }
    public function getActivityTypeNameAttribute()
    {
        $type = $this->attributes['activity_type'];

        return $this->attributes['activity_type_name'] = ManagerActivityLogs::TYPE_NAME[$type] ?? '';
        
    }

    public function getActivityNameAttribute()
    {
        $entity_id = $this->attributes['entity_id'];
        $type = $this->attributes['activity_type'];
        $activity_name = '';
        $userIDGroup = [
            ManagerActivityLogs::SNS_REWARD,
            ManagerActivityLogs::SNS_PENALTY,
            ManagerActivityLogs::DELETE_ACCOUNT,
            ManagerActivityLogs::RELOAD_COIN,
            ManagerActivityLogs::UPDATE_COIN,
            ManagerActivityLogs::REJECT_COIN,
            ManagerActivityLogs::BUSINESS_REQUEST_CONFIRM,
            ManagerActivityLogs::BUSINESS_REQUEST_REJECT
        ];


        if(in_array($type,$userIDGroup)){
            $userData = DB::table('users_detail')->where('user_id',$entity_id)->first();
            $activity_name = !empty($userData) ? $userData->name : '';
        }else if($type == ManagerActivityLogs::CREATE_MANAGER){
            $userData = DB::table('managers')->where('user_id',$entity_id)->first();
            $activity_name = !empty($userData) ? $userData->name : '';        
        }else if($type == ManagerActivityLogs::EDIT_CATEGORY){
            $categoryData = DB::table('category')->where('id',$entity_id)->first();
            $activity_name = !empty($categoryData) ? $categoryData->name : '';
        }else if($type == ManagerActivityLogs::EDIT_LANGUAGE_CATEGORY){
            $categoryData = DB::table('category_languages')->where('id',$entity_id)->first();
            $activity_name = !empty($categoryData) ? $categoryData->name : '';
        }else if($type == ManagerActivityLogs::EDIT_SETTINGS){
            $configData = DB::table('config')->where('id',$entity_id)->first();
            $activity_name = !empty($configData) ?  Str::ucfirst(str_replace('_', ' ', $configData->key)) : '';
        }else if($type == ManagerActivityLogs::EDIT_LANGUAGE_SETTINGS){
            $configData = DB::table('config_languages')->select('config.*')->join('config','config.id','config_languages.config_id')->where('config_languages.id',$entity_id)->first();
            $activity_name = !empty($configData) ?  Str::ucfirst(str_replace('_', ' ', $configData->key)) : '';
        }else if($type == ManagerActivityLogs::EDIT_CREDIT_SETTINGS){
            $configData = DB::table('credit_plans')->select('package_plans.*')->join('package_plans','package_plans.id','credit_plans.package_plan_id')->where('credit_plans.id',$entity_id)->first();
            $activity_name = !empty($configData) ?  $configData->name : '';
        }

        return $this->attributes['activity_name'] = $activity_name; //$entity_id.$activity_name;
    }
}
