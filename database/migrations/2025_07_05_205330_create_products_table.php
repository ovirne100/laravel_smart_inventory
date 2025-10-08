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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->string('name', 25);
            $table->string('reference', 50)->nullable();
            $table->string('unit_measurement', 10)->nullable();
            $table->string('batch', 10);
            $table->date('expiration_date')->nullable();
            $table->string('image',255)->nullable(); // Nueva columna para la imagen

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
