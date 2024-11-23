<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigCountryDetail extends Model
{
    protected $table = 'config_country_details';

    protected $fillable = [
        'config_id','value','country_code','created_at','updated_at'
    ];
}
