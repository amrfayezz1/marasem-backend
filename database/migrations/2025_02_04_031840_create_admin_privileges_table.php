<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminPrivilegesTable extends Migration
{
    public function up()
    {
        Schema::create('admin_privileges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('privileges')->default(json_encode([])); // Store privileges as JSON
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_privileges');
    }
}
