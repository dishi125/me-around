<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminChatNotificationDetail extends Model
{
    protected $table = 'admin_chat_notification_details';

    protected $fillable = [
        'admin_id',
        'chat_user_id',
        'is_receive',
        'created_at',
        'updated_at',
    ];
}
