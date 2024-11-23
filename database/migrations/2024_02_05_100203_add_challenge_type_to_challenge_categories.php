<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChallengeTypeToChallengeCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('challenge_categories', function (Blueprint $table) {
            $table->integer('challenge_type')->nullable()->comment('Challenge->1, Period challenge->2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('challenge_categories', function (Blueprint $table) {
            //
        });
    }
}
