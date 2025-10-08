<?php

namespace App\Http\Controllers;

use App\Models\InventoryDetail;
use Illuminate\Http\Request;

class InventoryController 
{
    // GET /api/inventory → lista todos los detalles del inventario
    public function index()
    {
        $inventory = InventoryDetail::with(['product', 'inventory', 'location'])->get();
        return response()->json($inventory);
    }

    // POST /api/inventory → crear un nuevo detalle de inventario
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'inventory_id' => 'required|exists:inventories,id',
            'location_id' => 'required|exists:locations,id',
            'current_stock' => 'required|integer',
            'min_threshold' => 'required|integer',
        ]);

        $inventoryDetail = InventoryDetail::create($validated);
        return response()->json($inventoryDetail, 201);
    }

    // GET /api/inventory/{id} → mostrar un detalle específico
    public function show(InventoryDetail $inventoryDetail)
    {
        return response()->json($inventoryDetail->load(['product', 'inventory', 'location']));
    }

    // PUT /api/inventory/{id} → actualizar un detalle de inventario
    public function update(Request $request, InventoryDetail $inventoryDetail)
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'inventory_id' => 'sometimes|exists:inventories,id',
            'location_id' => 'sometimes|exists:locations,id',
            'current_stock' => 'sometimes|integer',
            'min_threshold' => 'sometimes|integer',
        ]);

        $inventoryDetail->update($validated);
        return response()->json($inventoryDetail->load(['product', 'inventory', 'location']));
    }

    // DELETE /api/inventory/{id} → eliminar un detalle de inventario
    public function destroy(InventoryDetail $inventoryDetail)
    {
        $inventoryDetail->delete();
        return response()->json(null, 204);
    }
}
