<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('section', ['home', 'profile','popup','category'])->default('home');
            $table->unsignedBigInteger('entity_type_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('entity_type_id')->references('id')->on('entity_types')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('category')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('banners');
    }
}
