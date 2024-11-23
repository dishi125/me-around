<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BigCategorySetting extends Model
{
    protected $table = 'big_category_settings';

    protected $fillable = [
        'big_category_id','is_show','order','country_code','created_at','updated_at'
    ];
}
