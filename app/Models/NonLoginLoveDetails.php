<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonLoginLoveDetails extends Model
{
    protected $table = 'non_login_love_details';
    protected $fillable = [
            'device_id',  'card_log', 'created_at','updated_at'
    ];
}
