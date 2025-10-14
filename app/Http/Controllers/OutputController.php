<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OutputService;

class OutputController extends Controller
{
    protected $service;

    public function __construct(OutputService $service)
    {
        $this->service = $service;
    }

    /**
     * 📄 Listar todas las salidas
     */
    public function index()
    {
        $data = $this->service->listAll();
        return response()->json(['message' => 'Listado de salidas', 'data' => $data]);
    }

    /**
     * ➕ Crear nueva salida
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit' => 'nullable|string|max:20',
            'lot' => 'nullable|string|max:50',
            'user_id' => 'required|exists:users,id',
            // 🔹 inventory_id ahora es opcional
            'inventory_id' => 'nullable|exists:inventories,id',
        ]);

        $result = $this->service->create($validated);

        if ($result['error']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json([
            'message' => 'Salida creada exitosamente',
            'data' => $result['data']
        ], 201);
    }

    /**
     * 🔍 Mostrar detalles de una salida
     */
    public function show($id)
    {
        $data = $this->service->find($id);
        return response()->json(['message' => 'Detalles de la salida', 'data' => $data]);
    }

    /**
     * ✏️ Actualizar una salida existente
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'quantity' => 'sometimes|integer|min:1',
            'unit' => 'sometimes|string|max:20',
            'lot' => 'sometimes|string|max:50',
            'user_id' => 'sometimes|exists:users,id',
            'inventory_id' => 'sometimes|exists:inventories,id',
        ]);

        $result = $this->service->update($id, $validated);

        if ($result['error']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json([
            'message' => 'Salida actualizada exitosamente',
            'data' => $result['data']
        ]);
    }

    /**
     * 📊 Obtener resumen de salidas
     */
    public function summary()
    {
        return response()->json($this->service->summary());
    }

    /**
     * 🗑️ Eliminar una salida
     */
    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Salida eliminada exitosamente']);
    }

    /**
     * 📦 Datos para formularios
     */
    public function formData()
    {
        return response()->json($this->service->formData());
    }
}
