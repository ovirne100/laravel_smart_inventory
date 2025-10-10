<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * 📦 Crear inventario
     */
    public function createInventory(Request $request)
    {
        $validated = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'stock'        => 'required|numeric|min:0',
            'min_stock'    => 'nullable|numeric|min:0',
            'user_id'      => 'required|exists:users,id',
        ]);

        $inventory = Inventory::create($validated);

        // Generar alertas para inventarios viejos
        $this->generateAlertsForOldInventories();

        return response()->json([
            'status'  => 'success',
            'message' => 'Inventario creado exitosamente',
            'data'    => $inventory->load('alerts', 'product', 'warehouse', 'user')
        ], 201);
    }

    /**
     * 🔄 Ajustar stock (entrada o salida)
     */
    public function adjustStock(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity'  => 'required|numeric|min:0',
            'operation' => 'required|in:add,subtract',
        ]);

        $inventory = Inventory::findOrFail($id);

        DB::transaction(function () use ($inventory, $validated) {
            if ($validated['operation'] === 'add') {
                $inventory->stock += $validated['quantity'];
            } else {
                $inventory->stock = max(0, $inventory->stock - $validated['quantity']);
            }

            $inventory->save(); // Dispara eventos y verifica alertas
        });

        $this->generateAlertsForOldInventories();

        return response()->json([
            'status'  => 'success',
            'message' => 'Stock actualizado correctamente',
            'data'    => $inventory->load('alerts', 'product', 'warehouse', 'user')
        ]);
    }

    /**
     * 🗑️ Eliminar inventario
     */
    public function deleteInventory($id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Registro de inventario eliminado correctamente'
        ]);
    }

    /**
     * 🚨 Generar alertas para inventarios antiguos sin alertas activas
     */
    private function generateAlertsForOldInventories()
    {
        $inventories = Inventory::with('alerts')->get();

        foreach ($inventories as $inventory) {
            $hasActiveAlert = $inventory->alerts()
                ->where('status', 'active')
                ->exists();

            if (!$hasActiveAlert && $inventory->stock <= $inventory->min_stock) {
                \App\Http\Controllers\AlertController::checkStock($inventory);
            }
        }
    }
}
