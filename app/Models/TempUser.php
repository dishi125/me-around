<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempUser extends Model
{
    protected $table = "temp_users";

    protected $fillable = [
        'social_id',
        'social_type',
        'email',
        'username',
        'auth_code',
        'apple_refresh_token',
        'apple_access_token',
    ];
}
