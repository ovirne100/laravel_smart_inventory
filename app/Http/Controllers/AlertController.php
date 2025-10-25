<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AlertController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

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
        ]);

        $alerts = $this->alertService->getAlerts($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Listado de alertas obtenido correctamente.',
            'data' => $alerts,
        ], 200);
    }

    /**
     * ✅ Resolver una alerta específica
     */
    public function resolve(int $id): JsonResponse
    {
        try {
            $alert = $this->alertService->resolveAlert($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Alerta resuelta correctamente.',
                'data' => $alert,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alerta no encontrada.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al resolver la alerta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📊 Obtener estadísticas de alertas (por tipo y estado)
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->alertService->getStats();

            return response()->json([
                'status' => 'success',
                'message' => 'Estadísticas de alertas obtenidas correctamente.',
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
            ], 500);
        }
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
                'message' => 'Inventario verificado y alertas actualizadas correctamente.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al verificar el inventario: ' . $e->getMessage(),
            ], 500);
        }
    }
}
