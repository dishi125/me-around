<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigLanguages extends Model
{
    protected $table = 'config_languages';

    protected $fillable = [
        'value','config_id','language_id','created_at','updated_at'
    ];
}
