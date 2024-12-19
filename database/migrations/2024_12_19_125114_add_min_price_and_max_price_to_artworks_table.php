<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up()
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->decimal('min_price', 10, 2)->nullable()->after('sizes_prices');
            $table->decimal('max_price', 10, 2)->nullable()->after('min_price');
        });
    }

    public function down()
    {
        Schema::table('artworks', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'max_price']);
        });
    }
};
