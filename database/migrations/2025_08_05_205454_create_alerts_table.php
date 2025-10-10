<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Campos de texto simples en lugar de ENUM
            $table->string('alert_type')->default('bajo_stock'); // Ej: 'bajo_stock', 'sin_stock'
            $table->string('status')->default('pendiente');      // Ej: 'pendiente', 'resuelto'

            $table->string('message');
            $table->dateTime('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
