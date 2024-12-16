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
        Schema::create('customized_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Client who submitted the order
            $table->unsignedBigInteger('artwork_id'); // Related artwork
            $table->string('desired_size'); // Custom size requested by the client
            $table->decimal('offering_price', 10, 2); // Client's offered price
            $table->unsignedBigInteger('address_id'); // Address for delivery
            $table->text('description')->nullable(); // Additional details
            $table->string('status')->default('pending'); // Status (e.g., pending, accepted, rejected)
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('artwork_id')->references('id')->on('artworks')->onDelete('cascade');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customized_orders');
    }
};
