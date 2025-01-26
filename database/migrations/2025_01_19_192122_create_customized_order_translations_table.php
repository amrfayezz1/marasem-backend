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
        Schema::create('customized_order_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customized_order_id');
            $table->unsignedBigInteger('language_id');
            $table->text('description');
            $table->timestamps();

            $table->foreign('customized_order_id')->references('id')->on('customized_orders')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customized_order_translations');
    }
};
