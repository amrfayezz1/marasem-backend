<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('name')->nullable(); // Name field
            $table->string('phone')->nullable(); // Phone field
            $table->string('country_code')->nullable(); // Country code field
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['name', 'phone', 'country_code']);
        });
    }
};
