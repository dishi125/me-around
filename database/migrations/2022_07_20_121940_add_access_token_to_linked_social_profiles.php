<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccessTokenToLinkedSocialProfiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('linked_social_profiles', function (Blueprint $table) {
            $table->text('access_token')->nullable();
            $table->dateTime('token_refresh_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('linked_social_profiles', function (Blueprint $table) {
            $table->dropColumn('access_token');
            $table->dropColumn('token_refresh_date');
        });
    }
}
