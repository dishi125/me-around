<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserCardLevel extends Model
{
    protected $table = 'user_card_levels';
    protected $fillable = [
        'user_card_id','created_at','updated_at', 'card_level', 'card_level_status'
    ];
}
