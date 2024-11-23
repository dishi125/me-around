<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCardAppliedHistory extends Model
{
    protected $table = 'user_card_applied_histories';
    protected $fillable = [
        'user_id','old_card_id','new_card_id','applied_date','created_at','updated_at'
    ];
}
