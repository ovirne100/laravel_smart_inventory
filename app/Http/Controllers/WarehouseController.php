<?php

namespace App\Http\Controllers;

use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class WarehouseController extends Controller
{
    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->middleware('auth:sanctum');
        $this->warehouseService = $warehouseService;
    }

    /**
     * 📄 Listar todos los almacenes (con filtros, includes, sort y paginación)
     */
    public function index(): JsonResponse
    {
        try {
            $warehouses = $this->warehouseService->getAllWarehouses(request());

            return response()->json([
                'status'  => 'success',
                'message' => 'Almacenes obtenidos correctamente.',
                'data'    => $warehouses,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al obtener almacenes: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener almacenes.',
            ], 500);
        }
    }

    /**
     * ➕ Crear nuevo almacén
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:30|unique:warehouses,name',
            'address'  => 'required|string|max:25',
            'capacity' => 'required|numeric|min:0',
        ]);

        try {
            $warehouse = $this->warehouseService->createWarehouse($validated);

            return response()->json([
                'status'  => 'success',
                'message' => '✅ Almacén creado correctamente.',
                'data'    => $warehouse,
            ], 201);
        } catch (Exception $e) {
            Log::error('❌ Error al crear almacén: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => config('app.debug') ? $e->getMessage() : 'Error al crear el almacén.',
            ], 500);
        }
    }

    /**
     * 🔍 Mostrar un almacén específico (incluyendo relaciones)
     */
    public function show(int $id): JsonResponse
    {
        try {
            $warehouse = $this->warehouseService->getWarehouseById($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Almacén obtenido correctamente.',
                'data'    => $warehouse,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Almacén no encontrado.',
            ], 404);
        }
    }

    /**
     * ✏️ Actualizar almacén
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'sometimes|string|max:30|unique:warehouses,name,' . $id,
            'address'  => 'sometimes|string|max:25',
            'capacity' => 'sometimes|numeric|min:0',
        ]);

        try {
            $warehouse = $this->warehouseService->updateWarehouse($validated, $id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Almacén actualizado correctamente.',
                'data'    => $warehouse,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al actualizar almacén: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al actualizar el almacén.',
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar almacén
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->warehouseService->deleteWarehouse($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Almacén eliminado correctamente.',
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al eliminar almacén: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 📊 Obtener estadísticas del almacén
     */
    public function stats(int $id): JsonResponse
    {
        try {
            $stats = $this->warehouseService->getWarehouseStats($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Estadísticas obtenidas correctamente.',
                'data'    => $stats,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al obtener estadísticas: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener estadísticas.',
            ], 500);
        }
    }
}
