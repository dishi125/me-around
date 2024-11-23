<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCardLog extends Model
{
    protected $table = 'user_card_logs';
    protected $fillable = [
        'user_id','card_id','card_log','created_at','updated_at','love_count'
    ];

    const FEED = 'feed';
    const OPEN_APP = 'open_app';
    const ALLOW_FEED = 3;
    const MISSED_FOR_SAD_STATUS = 1;
    const MISSED_FOR_DEAD_STATUS = 10;

}
