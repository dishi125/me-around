<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSettingLanguage extends Model
{
    protected $table = 'menu_setting_languages';

    protected $fillable = [
        'menu_name','menu_id','language_id', 'created_at','updated_at'
    ];
}
