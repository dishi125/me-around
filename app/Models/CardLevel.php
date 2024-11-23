<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardLevel extends Model
{
    protected $table = 'card_levels';

    protected $fillable = [
        'level_name', 'start', 'end', 'range', 'created_at','updated_at'
    ];

    CONST DEFAULT_LEVEL = 1;
    CONST MIDDLE_LEVEL = 3;
    CONST LAST_LEVEL = 5;

    const LAST_LEVEL_COUNT = 350;
}
