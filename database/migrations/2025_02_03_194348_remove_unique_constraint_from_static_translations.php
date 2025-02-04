<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueConstraintFromStaticTranslations extends Migration
{
    public function up()
    {
        Schema::table('static_translations', function (Blueprint $table) {
            $table->dropUnique('static_translations_token_unique'); // Remove unique constraint
        });
    }

    public function down()
    {
        Schema::table('static_translations', function (Blueprint $table) {
            $table->unique('token'); // Add unique constraint back if rolling back
        });
    }
};