<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetaKeywordUrlPictureToCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            // Add new columns after the existing columns.
            $table->string('meta_keyword')->after('description');
            $table->string('url')->after('meta_keyword');
            $table->string('picture')->after('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['meta_keyword', 'url', 'picture']);
        });
    }
}
