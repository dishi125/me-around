<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorySettings extends Model
{
    protected $table = 'category_settings';

    protected $fillable = [
        'category_id','is_show','order','country_code','created_at','updated_at','is_hidden','status_id','menu_key'
    ];
}
