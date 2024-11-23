<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBankDetail extends Model
{
    protected $table = 'user_bank_details';
    protected $fillable = [
        'user_id', 'recipient_name','bank_name','bank_account_number','created_at','updated_at'
    ];
}
