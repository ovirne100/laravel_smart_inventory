<?php

namespace App\Services;

use App\Models\Inventory;

class InventoryService
{
    public function getAll($filters = [])
    {
        return Inventory::query()
            ->where('warehouse_id', $filters['warehouse_id'] ?? null)
            ->when($filters['min_stock'] ?? null, fn($q, $min) => $q->where('current_stock', '>=', $min))
            ->when($filters['max_stock'] ?? null, fn($q, $max) => $q->where('current_stock', '<=', $max))
            ->paginate(10);
    }

    public function updateStock(Inventory $inventory, int $cantidad, string $tipo = 'entrada')
    {
        if ($tipo === 'entrada') {
            $inventory->increment('current_stock', $cantidad);
        } else {
            $inventory->decrement('current_stock', $cantidad);
        }

        $inventory->refresh();
        return $inventory;
    }
}
