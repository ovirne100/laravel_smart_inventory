<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('outputs', function (Blueprint $table) {
            $table->id(); // ID salida

            // 🔗 Relaciones
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->integer('quantity'); // Cantidad
            $table->string('unit')->nullable(); // Unidad de medida
            $table->string('lot')->nullable(); // Lote

            // 👇 Aquí permitimos que el campo sea opcional (Auth::id() lo llenará)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('inventory_id')
                ->constrained('inventories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('outputs');
    }
};
