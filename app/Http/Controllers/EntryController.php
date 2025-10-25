<?php

namespace App\Http\Controllers;

use App\Services\EntryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EntryController extends Controller
{
    protected EntryService $entryService;

    public function __construct(EntryService $entryService)
    {
        $this->middleware('auth:sanctum');
        $this->entryService = $entryService;
    }

    // Listado de entradas
    public function index(): JsonResponse
    {
        $data = $this->entryService->getAllEntries();
        return response()->json([
            'status' => 'success',
            'message' => 'Listado de entradas obtenido correctamente',
            'data' => $data
        ]);
    }

    // Crear nueva entrada
    public function store(Request $request): JsonResponse
    {
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

        $userId = Auth::id();
        $entry = $this->entryService->createEntryWithInventoryAndUser($validated, $userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Entrada creada correctamente',
            'data' => $entry
        ]);
    }

    // Mostrar una entrada específica
    public function show(int $id): JsonResponse
    {
        $entry = $this->entryService->getEntryById($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Entrada obtenida correctamente',
            'data' => $entry
        ]);
    }

    // Actualizar una entrada
    public function update(Request $request, int $id): JsonResponse
    {
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
    }

    // Eliminar una entrada
    public function destroy(int $id): JsonResponse
    {
        $this->entryService->deleteEntry($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Entrada eliminada correctamente'
        ]);
    }

    // Resumen de entradas
    public function summary(): JsonResponse
    {
        $data = $this->entryService->getSummary();
        return response()->json([
            'status' => 'success',
            'message' => 'Resumen de entradas obtenido correctamente',
            'data' => $data
        ]);
    }

    // Datos para formularios (productos, proveedores, ubicaciones, almacenes)
    public function formData(): JsonResponse
    {
        $data = $this->entryService->formData();
        return response()->json([
            'status' => 'success',
            'message' => 'Datos para formularios obtenidos correctamente',
            'data' => $data
        ]);
    }
}
