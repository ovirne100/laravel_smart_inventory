<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('product_supplier', function (Blueprint $table) {
    // Crucial Pivot Data
    $table->decimal('unit_cost', 10, 2);
    $table->string('supplier_reference', 50)->nullable();

    // Foreign Keys (No autoincrement ID needed for the pivot table)
    $table->foreignId('supplier_id')
        ->constrained('suppliers')
        ->cascadeOnDelete();

    $table->foreignId('product_id')
        ->constrained('products')
        ->cascadeOnDelete();

    // Composite Primary Key
    $table->primary(['supplier_id', 'product_id']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
