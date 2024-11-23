<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsHideFieldInAssociationCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('association_categories', function (Blueprint $table) {
            $table->boolean('is_hide')->default(0)->after('associations_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('association_categories', function (Blueprint $table) {
            $table->dropColumn('is_hide');
        });
    }
}
