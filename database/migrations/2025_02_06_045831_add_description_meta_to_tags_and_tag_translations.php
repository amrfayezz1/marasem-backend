<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionMetaToTagsAndTagTranslations extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Update the tags table with common fields for subcategories.
        Schema::table('tags', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('meta_keyword')->nullable()->after('description');
            $table->string('url')->nullable()->after('meta_keyword');
            $table->string('image')->nullable()->after('url'); // will store the file path
        });

        // Update the tag_translations table with a description field.
        Schema::table('tag_translations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('tag_translations', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['description', 'meta_keyword', 'url', 'image']);
        });
    }
}
