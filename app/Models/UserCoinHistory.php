<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCoinHistory extends Model
{
    protected $table = 'user_coin_histories';
    protected $fillable = [
        'user_id', 'amount', 'transaction', 'type', 'entity_id', 'created_at', 'updated_at'
    ];

    const SOLD_CARD = 1;
    const PURCHASE_PRODUCT = 2;
    const REGISTER = 3;
    const REFERRAL_REGISTER = 4;
    const REFERRAL_REGISTER_BONUS = 5;

    const REGISTER_COIN = 500;
    const REFERRAL_REGISTER_COIN = 700;
    const REFERRAL_REGISTER_BONUS_COIN = 600;

    const DEBIT = 'debit';
    const CREDIT = 'credit';
}
