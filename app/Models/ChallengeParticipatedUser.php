<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeParticipatedUser extends Model
{
    protected $table = "challenge_participated_users";

    protected $fillable = [
        'challenge_id',
        'user_id',
    ];
}
