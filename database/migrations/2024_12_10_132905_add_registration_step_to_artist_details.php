<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('artist_details', function (Blueprint $table) {
            $table->smallInteger('registration_step')->default(1);  // Tracks the registration step (starting at step 1)
            $table->boolean('completed')->default(false);  // Marks if the registration is fully completed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artist_details', function (Blueprint $table) {
            $table->dropColumn(['registration_step', 'completed']);
        });
    }
};
