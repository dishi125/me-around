<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChallengeUserFollowingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('challenge_user_followings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('followed_by');
            $table->unsignedBigInteger('follows_to');
            $table->timestamps();

            $table->foreign('followed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('follows_to')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('challenge_user_followings');
    }
}
