<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMissedFeedCard extends Model
{
    protected $table = 'user_missed_feed_cards';
    protected $fillable = [
        'user_id','card_id','missed_date','created_at','updated_at'
    ];
}
