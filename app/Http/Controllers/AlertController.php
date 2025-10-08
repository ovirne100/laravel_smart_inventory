<?php

namespace App\Http\Controllers;

use App\Models\InventoryDetail;
use Illuminate\Http\Request;

class AlertController 
{
    /**
     * Endpoint principal de alertas
     */
    public function index(Request $request)
    {
        return $this->lowStock($request);
    }

    /**
     * Productos con stock bajo o crítico
     */
    public function lowStock(Request $request)
    {
        $query = InventoryDetail::with(['product.category', 'location.warehouse']);

        // Filtro por almacén si se pasa en la request
        if ($request->has('warehouse_id')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // Condición de stock bajo
        $query->whereColumn('current_stock', '<', 'min_threshold');

        // Paginación (10 por defecto)
        $lowStockItems = $query->paginate($request->get('per_page', 10));

        // Transformamos para un JSON más limpio
        $data = $lowStockItems->map(function ($item) {
            return [
                'product'        => $item->product->name ?? 'Desconocido',
                'category'       => $item->product->category->name ?? '-',
                'warehouse'      => $item->location->warehouse->name ?? '-',
                'location'       => $item->location->name ?? 'Sin ubicación',
                'aisle'          => $item->location->aisle ?? '-',
                'row'            => $item->location->row ?? '-',
                'current_stock'  => $item->current_stock,
                'threshold'      => $item->min_threshold,
                'critical'       => $item->current_stock == 0, // 🔴 crítico
            ];
        });

        return response()->json([
            'status' => 'success',
            'alerts' => $data,
            'pagination' => [
                'total' => $lowStockItems->total(),
                'current_page' => $lowStockItems->currentPage(),
                'last_page' => $lowStockItems->lastPage(),
            ]
        ]);
    }
}
