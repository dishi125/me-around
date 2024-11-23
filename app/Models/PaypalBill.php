<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaypalBill extends Model
{
    protected $table = "paypal_bills";

    protected $fillable = [
        'card_ver',
        'pay_work',
        'pay_goods',
        'pay_total',
        'start_date',
    ];
}
