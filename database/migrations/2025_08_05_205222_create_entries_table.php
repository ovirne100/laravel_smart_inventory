<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('entries', function (Blueprint $table) {
            $table->id(); // ID entrada
            $table->foreignId('product_id')->constrained('products'); // Producto
            $table->integer('quantity'); // Cantidad
            $table->string('unit')->nullable(); // Unidad de medida
            $table->string('lot')->nullable(); // Lote
            $table->foreignId('supplier_id')->constrained('suppliers'); // Proveedor
            // 🔹 Nuevos campos
            $table->string('ubicacion_interna'); // Ubicación interna
            $table->integer('stock'); // Stock
            $table->integer('stock_min'); // Stock mínimo
            $table->timestamps(); // created_at y updated_at
        });
    }

    public function down(): void {
        Schema::dropIfExists('entries');
    }
};
