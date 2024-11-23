<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetalkOptionLanguage extends Model
{
    protected $table = 'metalk_option_languages';

    protected $fillable = [
        'value','metalk_options_id','language_id','created_at','updated_at'
    ];
}
