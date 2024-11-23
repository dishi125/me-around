<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageNotificationStatus extends Model
{
    protected $table = 'messages_notification_status';
     
    protected $fillable = [
        'entity_type_id','entity_id','user_id','notification_status'
    ];

    protected $casts = [
        'id' => 'int',
        'entity_type_id' => 'int',
        'entity_id' => 'int',
        'user_id' => 'int',  
        'notification_status' => 'boolean',      
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
