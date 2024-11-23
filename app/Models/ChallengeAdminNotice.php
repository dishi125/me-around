<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeAdminNotice extends Model
{
    protected $table = "challenge_admin_notices";

    protected $fillable = [
        'title',
        'notice',
    ];
}
