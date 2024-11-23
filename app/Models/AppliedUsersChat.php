<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppliedUsersChat extends Model
{
    protected $table = "applied_users_chat";

    protected $fillable = [
        'admin_user_id',
        'applied_user_id',
        'country',
    ];

}
