<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminChatPinDetail extends Model
{
    protected $table = 'admin_chat_pin_details';

    protected $fillable = [
        'admin_id',
        'chat_user_id',
        'is_pin',
        'created_at',
        'updated_at',
    ];

}
