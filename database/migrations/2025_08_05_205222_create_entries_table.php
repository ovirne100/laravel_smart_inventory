<?php
// database/migrations/2025_09_21_000004_create_entries_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
       Schema::create('entries', function (Blueprint $table) {
    $table->id(); // ID entrada
    $table->foreignId('product_id')->constrained('products'); // Producto
    $table->integer('quantity'); // Cantidad
    $table->string('unit')->nullable(); // Unidad de medida (ej: pack)
    $table->string('lot')->nullable(); // Lote
    $table->foreignId('supplier_id')->constrained('suppliers'); // Proveedor
    $table->foreignId('user_id')->constrained('users'); // Usuario
    $table->foreignId('inventory_id')->constrained('inventories'); // Inventario
    $table->timestamps(); // created_at y updated_at
});

    }
    public function down(): void {
        Schema::dropIfExists('entries');
    }
};
