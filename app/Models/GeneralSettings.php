<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSettings extends Model
{
    protected $table = 'general_settings';

    protected $fillable = [
        'key',
        'label',
        'value',
        'created_at',
        'updated_at'
    ];

    /*const GENERAL_SETTING_OPTION = [
      ['key' => 'ios_app_version']
    ];*/
    const IOS_APP_VERSION = 'ios_app_version';
    const ANDROID_APP_VERSION = 'android_app_version';
    const DISPLAY_APP_VERSION = 'display_app_version';
    const LAST_DELETED_VIEW = 'last_deleted_view';
}
