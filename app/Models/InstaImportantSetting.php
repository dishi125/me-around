<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstaImportantSetting extends Model
{
    protected $table = "insta_important_settings";

    protected $fillable = [
        'field',
        'value',
    ];
}
