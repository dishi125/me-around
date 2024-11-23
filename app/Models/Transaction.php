<?php

namespace App\Models;

use App\Models\RequestBookingStatus;
use App\Models\UserDetail;
use App\Models\EntityTypes;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transaction';    
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'paypal_transaction_id','paypal_transaction_status','amount','currency'
    ];

    protected $casts = [
        'user_id' => 'int',
        'paypal_transaction_id' => 'string',
        'paypal_transaction_status' => 'string',
        'amount' => 'decimal:2',
        'currency' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
