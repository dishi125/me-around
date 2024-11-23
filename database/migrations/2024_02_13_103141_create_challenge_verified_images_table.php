<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChallengeVerifiedImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('challenge_verify_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('challenge_verify_id');
            $table->string('image');
            $table->timestamps();

            $table->foreign('challenge_verify_id')->references('id')->on('challenge_verify')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('challenge_verified_images');
    }
}
