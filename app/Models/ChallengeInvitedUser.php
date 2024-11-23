<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeInvitedUser extends Model
{
    protected $table = "challenge_invited_users";

    protected $fillable = [
        'challenge_id',
        'user_id',
        'invite_by',
        'is_admin_read',
    ];
}
