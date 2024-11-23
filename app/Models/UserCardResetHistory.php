<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCardResetHistory extends Model
{
    protected $table = 'user_card_reset_histories';
    protected $fillable = [
        'user_id', 'sell_card_id', 'card_level', 'love_count', 'card_level_status', 'created_at', 'updated_at'
    ];
}
