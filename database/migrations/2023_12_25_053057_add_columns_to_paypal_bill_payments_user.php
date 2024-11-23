<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToPaypalBillPaymentsUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paypal_bill_payments_user', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('paypal_bill_id');
            $table->string('payer_id')->nullable();
            $table->string('pay_goods')->nullable();
            $table->double('pay_total')->nullable();
            $table->string('simple_flag')->nullable();
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
