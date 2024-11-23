<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCardSellRequest extends Model
{
    protected $table = 'user_card_sell_requests';
    protected $fillable = [
        'card_id', 'status', 'card_level', 'created_at', 'updated_at'
    ];
}
