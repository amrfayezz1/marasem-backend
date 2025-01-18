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
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type'); // e.g., "deposit", "purchase"
            $table->decimal('amount', 10, 2); // Amount added or deducted
            $table->string('reference')->nullable(); // Reference to a related entity (e.g., Order ID, Artwork ID)
            $table->date('expiry_date')->nullable();
            $table->string('description')->nullable(); // Description of the transaction
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
