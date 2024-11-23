<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeInviteText extends Model
{
    protected $table = "challenge_invite_texts";

    protected $fillable = [
        'text',
    ];
}
