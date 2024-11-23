<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaypalRepaymentUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paypal_repayment_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paypal_payment_id');
            $table->string('status');
            $table->text('message');
            $table->string('oid')->nullable();
            $table->timestamps();

            $table->foreign('paypal_payment_id')->references('id')->on('paypal_bill_payments_user')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paypal_repayment_users');
    }
}
