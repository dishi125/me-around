<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSetting extends Model
{
    protected $table = 'menu_settings';

    protected $fillable = [
        'menu_name','menu_key','is_show', 'menu_order', 'created_at','updated_at','country_code'
    ];

    const MENU_LIST = [
        ['menu_name' => 'Character','menu_key' => 'character', 'is_show' => 1, 'menu_order' => 1],
        ['menu_name' => 'Dashboard','menu_key' => 'home', 'is_show' => 1, 'menu_order' => 2],
        ['menu_name' => 'Community','menu_key' => 'community', 'is_show' => 1, 'menu_order' => 3],
        ['menu_name' => 'Review','menu_key' => 'review', 'is_show' => 1, 'menu_order' => 4],
    ];
}
