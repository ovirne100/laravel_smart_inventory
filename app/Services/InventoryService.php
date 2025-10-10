<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AlertController;

class InventoryService
{
    /**
     * 🆕 Crear inventario y verificar alertas
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

        try {
            $inventory = DB::transaction(function () use ($validated) {
                $inventory = Inventory::create($validated);

                // 🚨 Verificar stock al crear
                AlertController::checkStock($inventory);

                return $inventory;
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Inventario creado correctamente.',
                'data'    => $inventory->load('alerts', 'product', 'warehouse', 'user')
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error al crear inventario: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al crear el inventario.'
            ], 500);
        }
    }

    /**
     * 🔄 Ajustar stock (entrada o salida) y generar alerta si aplica
     */
    public function adjustStock(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity'  => 'required|numeric|min:1',
            'operation' => 'required|in:add,subtract',
        ]);

        try {
            $inventory = Inventory::findOrFail($id);

            DB::transaction(function () use ($inventory, $validated) {
                if ($validated['operation'] === 'add') {
                    $inventory->stock += $validated['quantity'];
                    Log::info("📈 Entrada de {$validated['quantity']} unidades en inventario ID {$inventory->id}");
                } else {
                    $inventory->stock = max(0, $inventory->stock - $validated['quantity']);
                    Log::info("📉 Salida de {$validated['quantity']} unidades en inventario ID {$inventory->id}");
                }

                $inventory->save();

                // 🚨 Verificar si el ajuste requiere crear o resolver alertas
                AlertController::checkStock($inventory);
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Stock actualizado correctamente.',
                'data'    => $inventory->load('alerts', 'product', 'warehouse', 'user')
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al ajustar stock: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al actualizar el stock.'
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar inventario
     */
    public function deleteInventory($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            $inventory->delete();

            Log::warning("🗑️ Inventario ID {$id} eliminado.");

            return response()->json([
                'status'  => 'success',
                'message' => 'Inventario eliminado correctamente.'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar inventario: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al eliminar el inventario.'
            ], 500);
        }
    }

    /**
     * 🚨 Generar alertas para inventarios antiguos sin alerta activa
     */
    public function generateAlertsForOldInventories()
    {
        try {
            $inventories = Inventory::with('alerts', 'product')->get();

            foreach ($inventories as $inventory) {
                $hasActiveAlert = $inventory->alerts()
                    ->where('status', 'active')
                    ->exists();

                if (!$hasActiveAlert && $inventory->stock <= $inventory->min_stock) {
                    AlertController::checkStock($inventory);
                }
            }

            Log::info("🔍 Verificación masiva de alertas completada.");
        } catch (\Exception $e) {
            Log::error('❌ Error al generar alertas antiguas: ' . $e->getMessage());
        }
    }
}
