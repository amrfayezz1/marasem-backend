<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRateToCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            // Add the rate column as a decimal (adjust precision/scale as needed)
            $table->decimal('rate', 8, 2)->after('symbol')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('rate');
        });
    }
}
