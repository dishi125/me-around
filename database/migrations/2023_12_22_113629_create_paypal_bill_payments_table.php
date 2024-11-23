<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaypalBillPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paypal_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paypal_bill_id');
            $table->string('status')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payer_phone')->nullable();
            $table->string('payer_email')->nullable();
            $table->string('card_number')->nullable();
            $table->text('oid')->nullable();
            $table->string('card_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paypal_bill_payments');
    }
}
