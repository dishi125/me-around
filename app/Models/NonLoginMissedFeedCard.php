<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonLoginMissedFeedCard extends Model
{
    protected $table = 'non_login_missed_feed_cards';
    protected $fillable = [
        'user_id',  'missed_date', 'created_at','updated_at'
    ];
}
