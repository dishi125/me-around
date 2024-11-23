<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayIstaxToPaypalBillPaymentsUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paypal_bill_payments_user', function (Blueprint $table) {
            $table->string('pay_istax')->nullable();
            $table->double('pay_taxtotal')->nullable();
            $table->string('payer_no')->nullable();
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
