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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('original_total', 10, 2)->after('total_amount'); // Original total before discounts
            $table->unsignedBigInteger('promo_code_id')->nullable()->after('original_total');
            $table->decimal('marasem_credit_used', 10, 2)->default(0)->after('promo_code_id');
            $table->decimal('remaining_marasem_credit', 10, 2)->default(0)->after('marasem_credit_used');

            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('original_total');
            $table->dropColumn('promo_code_id');
            $table->dropColumn('marasem_credit_used');
            $table->dropColumn('remaining_marasem_credit');
        });
    }
};
