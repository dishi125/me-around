<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFeedLog extends Model
{
    protected $table = 'user_feed_logs';
    protected $fillable = [
        'user_id','card_id','feed_time','created_at','updated_at','is_admin_read','note'
    ];
}
