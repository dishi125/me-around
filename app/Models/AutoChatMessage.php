<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoChatMessage extends Model
{
    protected $table = "auto_chat_messages";

    protected $fillable = [
        'user_id',
        'message',
        'time',
        'week_day',
        'country_code',
    ];

}
