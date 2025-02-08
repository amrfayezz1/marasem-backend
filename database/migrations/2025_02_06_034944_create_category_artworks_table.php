<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryArtworksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('category_artworks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('artwork_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('artwork_id')->references('id')->on('artworks')->onDelete('cascade');

            // Prevent duplicate entries
            $table->unique(['category_id', 'artwork_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('category_artworks');
    }
}
