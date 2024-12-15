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
        Schema::create('artworks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('artist_id'); // Foreign key for artist
            $table->string('name');
            $table->json('photos')->nullable(); // JSON list of photo URLs
            $table->string('art_type');
            $table->string('artwork_status');
            $table->json('sizes_prices'); // JSON list of (size, price)
            $table->text('description');
            $table->boolean('customizable')->default(false);
            $table->string('duration')->nullable();
            $table->timestamps();

            $table->foreign('artist_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artworks');
    }
};
