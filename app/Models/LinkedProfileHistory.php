<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkedProfileHistory extends Model
{
    protected $table = 'linked_profile_histories';
    protected $fillable = [
        'shop_id',
        'social_name',
        'social_id',
        'access_token',
        'created_at',
        'updated_at',
        'last_disconnected_date'
    ];
}
