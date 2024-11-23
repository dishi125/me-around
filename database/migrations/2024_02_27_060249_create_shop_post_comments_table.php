<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopPostCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_post_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_post_id');
            $table->unsignedBigInteger('user_id');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('shop_post_id')->references('id')->on('shop_posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_post_comments');
    }
}
