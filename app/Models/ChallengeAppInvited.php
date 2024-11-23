<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeAppInvited extends Model
{
    protected $table = "challenge_app_invited";

    protected $fillable = [
        'invite_by',
        'is_admin_read',
    ];
}
