<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(
        protected AlertService $alertService
    ) {}

    /**
     * 📋 Listar alertas con filtros opcionales
     */
   public function index(Request $request): JsonResponse
{
    $validated = $request->validate([
        'alert_type' => 'nullable|in:bajo_stock,sin_stock',
        'status' => 'nullable|in:pendiente,resuelta',
        'product_id' => 'nullable|integer|exists:products,id',
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date|after_or_equal:date_from',
    ], [
        'alert_type.in' => 'El tipo de alerta debe ser: bajo_stock o sin_stock',
        'status.in' => 'El estado debe ser: pendiente o resuelta',
        'product_id.exists' => 'El producto especificado no existe',
    ]);

    // Traducir los filtros internos
    if (!empty($validated['alert_type'])) {
        $validated['alert_type'] = match ($validated['alert_type']) {
            'bajo_stock' => Alert::TYPE_LOW_STOCK,
            'sin_stock'  => Alert::TYPE_OUT_OF_STOCK,
            default      => $validated['alert_type'],
        };
    }

    if (!empty($validated['status'])) {
        $validated['status'] = match ($validated['status']) {
            'pendiente' => Alert::STATUS_ACTIVE,
            'resuelta'  => Alert::STATUS_RESOLVED,
            default     => $validated['status'],
        };
    }

    // Obtener alertas y mapear para incluir lote y referencia
    $alerts = $this->alertService->getAlerts($validated)->map(function($alert) {
        return [
            'id' => $alert->id,
            'message' => $alert->message,
            'alert_type' => $alert->alert_type,
            'status' => $alert->status,
            'date' => $alert->date,
            'resolved_at' => $alert->resolved_at,
            'product' => [
                'id' => $alert->product->id ?? null,
                'name' => $alert->product->name ?? 'Producto desconocido',
                'lot' => $alert->product->batch ?? null,        // ✅ usar batch
                'reference' => $alert->product->reference ?? null,
            ],
            'inventory' => $alert->inventory ?? null,
        ];
    });

    return response()->json([
        'status' => 'success',
        'message' => 'Listado de alertas obtenido correctamente',
        'data' => $alerts,
        'total' => $alerts->count(),
    ], 200);
}


    /**
     * 🔍 Mostrar una alerta específica
     */
    public function show(int $id): JsonResponse
    {
        try {
            $alert = Alert::with(['product', 'inventory'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $alert,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alerta no encontrada',
            ], 404);
        }
    }

    /**
     * ✅ Resolver una alerta manualmente
     */
    public function resolve(int $id): JsonResponse
    {
        try {
            $alert = $this->alertService->resolveAlert($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Alerta resuelta correctamente',
                'data' => $alert,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alerta no encontrada',
            ], 404);
        }
    }

    /**
     * 📊 Obtener estadísticas de alertas
     */
    public function stats(): JsonResponse
    {
        $stats = $this->alertService->getStats();

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ], 200);
    }

    /**
     * 🔄 Verificar todo el inventario y actualizar alertas
     */
    public function checkAll(): JsonResponse
    {
        try {
            $this->alertService->checkAllInventory();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventario verificado y alertas actualizadas correctamente',
                'stats' => $this->alertService->getStats(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al verificar el inventario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ℹ️ Obtener opciones válidas para filtros
     */
    public function filterOptions(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'alert_types' => [
                    [
                        'value' => Alert::TYPE_LOW_STOCK,
                        'label' => '📉 Stock Bajo'
                    ],
                    [
                        'value' => Alert::TYPE_OUT_OF_STOCK,
                        'label' => '🚫 Sin Stock'
                    ],
                ],
                'statuses' => [
                    [
                        'value' => Alert::STATUS_ACTIVE,
                        'label' => 'Pendiente'
                    ],
                    [
                        'value' => Alert::STATUS_RESOLVED,
                        'label' => 'Resuelta'
                    ],
                ]
            ]
        ], 200);
    }
}
