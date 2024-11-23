<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminMessageNotificationStatus extends Model
{
    protected $table = "admin_message_notification_statuses";

    protected $fillable = [
        'user_id',
        'notification_status'
    ];

}
