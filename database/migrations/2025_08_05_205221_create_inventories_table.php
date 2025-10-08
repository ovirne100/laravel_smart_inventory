<?php
/// database/migrations/2025_09_21_000003_create_inventories_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id(); // ID inventario
            $table->foreignId('product_id')->constrained('products'); // Producto
            $table->integer('stock')->default(0); // Stock actual
            $table->integer('min_stock')->default(0); // Stock mínimo
            $table->foreignId('user_id')->constrained('users'); // Usuario que gestiona
            $table->foreignId('warehouse_id')->constrained('warehouses'); // Almacén
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('inventories');
    }
};
