<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaypalBillPaymentUser extends Model
{
    protected $table = "paypal_bill_payments_user";

    protected $fillable = [
        'paypal_bill_id',
        'payment_method',
        'status',
        'payer_name',
        'payer_phone',
        'payer_email',
        'card_number',
        'oid',
        'card_name',
        'payer_id',
        'pay_goods',
        'pay_total',
        'simple_flag',
        'pay_istax',
        'pay_taxtotal',
        'payer_no',
        'start_date',
        'next_payment_date',
        'instagram_account',
        'is_hide_next_payment_date',
    ];

}
