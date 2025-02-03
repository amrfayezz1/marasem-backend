<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('name');
            $table->text('description')->nullable()->after('status');
        });
        Schema::table('category_translations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('description');
        });
        Schema::table('category_translations', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
