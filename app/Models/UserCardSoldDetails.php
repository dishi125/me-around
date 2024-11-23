<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCardSoldDetails extends Model
{
    protected $table = 'user_card_sold_details';
    protected $fillable = [
        'card_id', 'card_level', 'created_at', 'updated_at'
    ];
}
