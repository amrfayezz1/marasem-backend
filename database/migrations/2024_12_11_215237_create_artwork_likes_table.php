<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artwork_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('artwork_id');
            $table->timestamps();

            // Relationships
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('artwork_id')->references('id')->on('artworks')->onDelete('cascade');

            // Unique constraint to prevent duplicate likes
            $table->unique(['user_id', 'artwork_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artwork_likes');
    }
};
