<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToReloadCoinsRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reload_coins_request', function (Blueprint $table) {
            $table->decimal('supply_price', 15, 2)->default(0.00)->after('coin_amount');
            $table->decimal('vat_amount', 15, 2)->default(0.00)->after('supply_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reload_coins_request', function (Blueprint $table) {
            $table->dropColumn('supply_price');
            $table->dropColumn('vat_amount');
        });
    }
}
