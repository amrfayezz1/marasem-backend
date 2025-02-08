<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionAndStatusToCollectionsTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Update the collections table
        Schema::table('collections', function (Blueprint $table) {
            $table->string('description', 200)->nullable()->after('title');
            $table->boolean('active')->default(true)->after('description');
        });

        // Update the collection_translations table
        Schema::table('collection_translations', function (Blueprint $table) {
            $table->string('description', 200)->nullable()->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Revert changes in the collections table
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('active');
        });

        // Revert changes in the collection_translations table
        Schema::table('collection_translations', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
}
