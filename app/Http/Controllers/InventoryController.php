<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InventoryService;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * 📦 Crear inventario
     */
    public function store(Request $request)
    {
        return $this->inventoryService->createInventory($request);
    }

    /**
     * 🔄 Ajustar stock (entrada o salida)
     */
    public function adjustStock(Request $request, $id)
    {
        return $this->inventoryService->adjustStock($request, $id);
    }

    /**
     * 🗑️ Eliminar inventario
     */
    public function destroy($id)
    {
        return $this->inventoryService->deleteInventory($id);
    }
}
