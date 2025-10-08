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
        $entry = $this->entryService->createEntry($request);

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
        $entry = $this->entryService->updateEntry($request, $id);

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
