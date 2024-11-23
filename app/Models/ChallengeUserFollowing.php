<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeUserFollowing extends Model
{
    protected $table = "challenge_user_followings";

    protected $fillable = [
        'followed_by',
        'follows_to',
    ];
}
