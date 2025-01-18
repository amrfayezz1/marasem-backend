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
            $table->unsignedBigInteger('appreciations_count')->default(0)->after('summary');
            $table->unsignedBigInteger('profile_views')->default(0)->after('appreciations_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artist_details', function (Blueprint $table) {
            $table->dropColumn('appreciations_count');
            $table->dropColumn('profile_views');
        });
    }
};
