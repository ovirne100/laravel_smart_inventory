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
    $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
    $table->string('alert_type');          // low_stock, critical, etc.
    $table->string('message');
    $table->enum('status', ['active', 'resolved'])->default('active');
    $table->timestamp('date')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();
});
    }
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
