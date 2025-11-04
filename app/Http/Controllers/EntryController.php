<?php

namespace App\Http\Controllers;

use App\Services\EntryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EntryController extends Controller
{
    protected EntryService $entryService;

    public function __construct(EntryService $entryService)
    {
        $this->middleware('auth:sanctum');
        $this->entryService = $entryService;
    }

    public function index(): JsonResponse
    {
        try {
            $data = $this->entryService->getAllEntries();
            return response()->json([
                'status' => 'success',
                'message' => 'Listado de entradas obtenido correctamente',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error en index(): ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener las entradas',
                'data' => null
            ], 500);
        }
    }

    // Endpoint para obtener lotes de un producto
    public function lotsByProduct(int $productId): JsonResponse
    {
        try {
            $lots = $this->entryService->getLotsByProduct($productId);
            return response()->json([
                'status' => 'success',
                'message' => 'Lotes obtenidos correctamente',
                'data' => $lots
            ]);
        } catch (\Exception $e) {
            Log::error('Error en lotsByProduct(): ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener los lotes',
                'data' => []
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id'   => 'required|exists:products,id',
                'quantity'     => 'required|numeric|min:1',
                'unit'         => 'nullable|string|max:50',
                'lot'          => 'nullable|string|max:100',
                'supplier_id'  => 'nullable|exists:suppliers,id',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'location_id'  => 'nullable|exists:locations,id',
                'min_stock'    => 'nullable|numeric|min:0',
            ]);

            // Validación del lote - SOLO si ya existen lotes para ese producto
            if (!empty($validated['lot'])) {
                $existingLots = $this->entryService->getLotsByProduct($validated['product_id']);

                // Solo validar si hay lotes previos registrados
                if (!empty($existingLots)) {
                    $lotExists = $this->entryService->validateLotForProduct(
                        $validated['product_id'],
                        $validated['lot']
                    );

                    if (!$lotExists) {
                        return response()->json([
                            'status' => 'error',
                            'message' => '❌ Lote no encontrado para este producto. Lotes disponibles: ' . implode(', ', $existingLots),
                            'data' => null
                        ], 422);
                    }
                }
            }

            $userId = Auth::id();
            $entry = $this->entryService->createEntryWithInventoryAndUser($validated, $userId);

            return response()->json([
                'status' => 'success',
                'message' => '✅ Entrada creada correctamente',
                'data' => $entry
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en store(): ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear la entrada',
                'data' => null
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $entry = $this->entryService->getEntryById($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Entrada obtenida correctamente',
                'data' => $entry
            ]);
        } catch (\Exception $e) {
            Log::error('Error en show(): ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener la entrada',
                'data' => null
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id'   => 'sometimes|exists:products,id',
                'quantity'     => 'sometimes|numeric|min:1',
                'unit'         => 'nullable|string|max:50',
                'lot'          => 'nullable|string|max:100',
                'supplier_id'  => 'nullable|exists:suppliers,id',
                'warehouse_id' => 'nullable|exists:warehouses,id',
                'location_id'  => 'nullable|exists:locations,id',
                'min_stock'    => 'nullable|numeric|min:0',
            ]);

            $entry = $this->entryService->updateEntry($validated, $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Entrada actualizada correctamente',
                'data' => $entry
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en update(): ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar la entrada',
                'data' => null
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->entryService->deleteEntry($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Entrada eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error en destroy(): ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la entrada'
            ], 500);
        }
    }

    public function summary(): JsonResponse
    {
        try {
            $data = $this->entryService->getSummary();
            return response()->json([
                'status' => 'success',
                'message' => 'Resumen de entradas obtenido correctamente',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error en summary(): ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener el resumen',
                'data' => null
            ], 500);
        }
    }

    public function formData(): JsonResponse
    {
        try {
            $data = $this->entryService->formData();
            return response()->json([
                'status' => 'success',
                'message' => 'Datos para formularios obtenidos correctamente',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error en formData(): ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al cargar datos del formulario',
                'data' => [
                    'products' => [],
                    'suppliers' => [],
                    'locations' => [],
                    'warehouses' => []
                ]
            ], 500);
        }
    }
}
