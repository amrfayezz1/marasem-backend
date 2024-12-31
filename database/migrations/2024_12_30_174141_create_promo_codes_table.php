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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->integer('max_usages')->nullable();
            $table->integer('usages')->default(0);
            $table->enum('status', ['active', 'expired', 'disabled'])->default('active');
            $table->date('expiry_date')->nullable();
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->decimal('discount_value', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
