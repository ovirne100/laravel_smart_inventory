<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('product_exits', function (Blueprint $table) {
    $table->id(); 
    $table->unsignedBigInteger('user_id');
    $table->dateTime('exit_date');
    $table->string('exit_type', 20);

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});

    }

    public function down(): void
    {
        Schema::dropIfExists('product_exits');
    }
};
