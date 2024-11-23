<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaypalRepaymentUser extends Model
{
    protected $table = "paypal_repayment_users";

    protected $fillable = [
        'paypal_payment_id',
        'status',
        'message',
        'oid',
        'product_name',
        'amount'
    ];

    public function billpayment()
    {
        return $this->hasOne(PaypalBillPaymentUser::class,'id','paypal_payment_id');
    }
}
