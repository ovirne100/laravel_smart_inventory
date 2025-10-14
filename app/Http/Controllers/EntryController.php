<?php

namespace App\Http\Controllers;

use App\Services\EntryService;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    protected $entryService;

    public function __construct(EntryService $entryService)
    {
        $this->entryService = $entryService;
    }

    /**
     * 📄 Listar todas las entradas
     */
    public function index()
    {
        $data = $this->entryService->getAllEntries();

        return response()->json([
            'message' => 'Listado de entradas',
            'data'    => $data
        ]);
    }

    /**
     * ➕ Crear nueva entrada
     */
    public function store(Request $request)
    {
        // Validación de los campos según tu formulario
        $validated = $request->validate([
            'product_id'        => 'required|exists:products,id',
            'quantity'          => 'required|integer|min:1',
            'unit'              => 'nullable|string|max:20',
            'lot'               => 'nullable|string|max:50',
            'supplier_id'       => 'required|exists:suppliers,id',
            'ubicacion_interna' => 'required|string|max:255',
            'stock'             => 'required|integer|min:0',
            'stock_min'         => 'required|integer|min:0',
        ]);

        $entry = $this->entryService->createEntry($validated);

        return response()->json([
            'message' => 'Entrada creada exitosamente',
            'data'    => $entry
        ], 201);
    }

    /**
     * 🔍 Mostrar una entrada
     */
    public function show($id)
    {
        $entry = $this->entryService->getEntryById($id);

        return response()->json([
            'message' => 'Detalles de la entrada',
            'data'    => $entry
        ]);
    }

    /**
     * ✏️ Actualizar una entrada
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'product_id'        => 'sometimes|exists:products,id',
            'quantity'          => 'sometimes|integer|min:1',
            'unit'              => 'sometimes|string|max:20',
            'lot'               => 'sometimes|string|max:50',
            'supplier_id'       => 'sometimes|exists:suppliers,id',
            'ubicacion_interna' => 'sometimes|string|max:255',
            'stock'             => 'sometimes|integer|min:0',
            'stock_min'         => 'sometimes|integer|min:0',
        ]);

        $entry = $this->entryService->updateEntry($validated, $id);

        return response()->json([
            'message' => 'Entrada actualizada exitosamente',
            'data'    => $entry
        ]);
    }

    /**
     * 📊 Resumen de entradas
     */
    public function summary()
    {
        $summary = $this->entryService->getSummary();

        return response()->json($summary);
    }

    /**
     * 🗑️ Eliminar una entrada
     */
    public function destroy($id)
    {
        $this->entryService->deleteEntry($id);

        return response()->json(['message' => 'Entrada eliminada exitosamente']);
    }

    /**
     * 📦 Listas para selects
     */
    public function formData()
    {
        $data = $this->entryService->getFormData();

        return response()->json($data);
    }
}
