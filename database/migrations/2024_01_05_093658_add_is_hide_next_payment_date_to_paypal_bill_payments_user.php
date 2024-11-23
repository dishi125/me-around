<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsHideNextPaymentDateToPaypalBillPaymentsUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paypal_bill_payments_user', function (Blueprint $table) {
            $table->boolean('is_hide_next_payment_date')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paypal_bill_payments_user', function (Blueprint $table) {
            //
        });
    }
}
