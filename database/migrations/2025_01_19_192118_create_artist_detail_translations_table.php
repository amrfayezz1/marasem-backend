<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('artist_detail_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('artist_detail_id');
            $table->unsignedBigInteger('language_id');
            $table->text('summary');
            $table->timestamps();

            $table->foreign('artist_detail_id')->references('id')->on('artist_details')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_detail_translations');
    }
};
