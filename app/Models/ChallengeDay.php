<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeDay extends Model
{
    protected $table = "challenge_days";

    protected $fillable = [
        'challenge_id',
        'day',
    ];
}
