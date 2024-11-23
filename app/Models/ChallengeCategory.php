<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeCategory extends Model
{
    protected $table = "challenge_categories";

    protected $fillable = [
        'name',
        'order',
        'is_hidden',
        'challenge_type',
    ];

    const CHALLENGE = 1;
    const PERIODCHALLENGE = 2;
}
