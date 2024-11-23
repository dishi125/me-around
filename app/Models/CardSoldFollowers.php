<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardSoldFollowers extends Model
{
    protected $table = 'card_sold_followers';
    protected $fillable = [
        'sold_id', 'user_id', 'status', 'follower_level', 'created_at', 'updated_at'
    ];

    const FOLLOWERS = 1;
    const GRAND_FOLLOWERS = 2;
    const GREAT_GRAND_FOLLOWERS = 3;

    const FOLLOWERS_COIN = 5000;
    const GRAND_FOLLOWERS_COIN = 2500;
    const GREAT_GRAND_FOLLOWERS_COIN = 1200;

    const SOLD_CARD_COIN = 10000;
}
