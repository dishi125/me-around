<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeUserPoint extends Model
{
    protected $table = "challenge_user_points";

    protected $fillable = [
        'user_id',
        'bp',
    ];
}
