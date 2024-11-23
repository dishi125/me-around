<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeNotice extends Model
{
    protected $table = "challenge_notices";

    protected $fillable = [
        'user_id',
        'to_user_id',
        'notify_type',
        'challenge_id',
    ];
}
