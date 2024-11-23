<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSetting extends Model
{
    protected $table = 'menu_settings';

    protected $fillable = [
        'menu_name','menu_key','is_show', 'menu_order', 'created_at','updated_at','country_code', 'category_option'
    ];

    const DEFAULT_TAB = 'home';
    const MENU_LIST = [
        ['menu_name' => 'Character','menu_key' => 'character', 'is_show' => 1, 'menu_order' => 1, 'is_category_toggle' => 0],
        ['menu_name' => 'Dashboard','menu_key' => 'home', 'is_show' => 1, 'menu_order' => 2, 'is_category_toggle' => 1],
        ['menu_name' => 'Group Chat','menu_key' => 'group_chat', 'is_show' => 0, 'menu_order' => 8, 'is_category_toggle' => 0],
        ['menu_name' => 'Community','menu_key' => 'community', 'is_show' => 1, 'menu_order' => 3, 'is_category_toggle' => 0],
        ['menu_name' => 'Review','menu_key' => 'review', 'is_show' => 1, 'menu_order' => 4, 'is_category_toggle' => 0],
        ['menu_name' => 'Dashboard2','menu_key' => 'home2', 'is_show' => 0, 'menu_order' => 5, 'is_category_toggle' => 0],
        ['menu_name' => 'Dashboard3','menu_key' => 'home3', 'is_show' => 0, 'menu_order' => 6, 'is_category_toggle' => 0],
        ['menu_name' => 'Dashboard4','menu_key' => 'home4', 'is_show' => 0, 'menu_order' => 7, 'is_category_toggle' => 0],
    ];

    const MENU_CARD_LIST = ['home','home2','home3','home4'];
}
