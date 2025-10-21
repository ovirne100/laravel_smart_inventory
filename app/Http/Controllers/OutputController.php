<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\OutputService;

class OutputController extends Controller
{
    protected OutputService $service;

    public function __construct(OutputService $service)
    {
        $this->service = $service;
    }

    /**
     * 📦 Listar todas las salidas
     */
    public function index()
    {
        $data = $this->service->listAll();

        return response()->json([
            'status'  => 'success',
            'message' => 'Listado de salidas',
            'data'    => $data,
        ]);
    }

    /**
     * ➕ Crear nueva salida
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'inventory_id' => 'nullable|exists:inventories,id',
            'quantity'     => 'required|numeric|min:1',
            'unit'         => 'nullable|string|max:20',
            'lot'          => 'nullable|string|max:50',
        ]);

        // ✅ Añadir el usuario autenticado
        $validated['user_id'] = Auth::id();

        $result = $this->service->create($validated);

        // Si hay error lógico, devuelve 422 (Unprocessable Entity)
        return response()->json([
            'status'  => $result['error'] ? 'error' : 'success',
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ], $result['error'] ? 422 : 201);
    }

    /**
     * 📄 Mostrar detalles
     */
    public function show($id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El identificador de salida no es válido.',
            ], 400);
        }

        $data = $this->service->find((int) $id);

        return response()->json([
            'status'  => $data['error'] ? 'error' : 'success',
            'message' => $data['message'] ?? 'Detalles de la salida',
            'data'    => $data['data'] ?? null,
        ], $data['error'] ? 404 : 200);
    }

    /**
     * ✏️ Actualizar salida
     */
    public function update(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El identificador de salida no es válido.',
            ], 400);
        }

        $validated = $request->validate([
            'product_id'   => 'sometimes|exists:products,id',
            'inventory_id' => 'sometimes|exists:inventories,id',
            'quantity'     => 'sometimes|numeric|min:1',
            'unit'         => 'sometimes|string|max:20',
            'lot'          => 'sometimes|string|max:50',
        ]);

        $validated['user_id'] = Auth::id();

        $result = $this->service->update((int) $id, $validated);

        return response()->json([
            'status'  => $result['error'] ? 'error' : 'success',
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ], $result['error'] ? 422 : 200);
    }

    /**
     * 🗑️ Eliminar salida
     */
    public function destroy($id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El identificador de salida no es válido.',
            ], 400);
        }

        $result = $this->service->delete((int) $id);

        return response()->json([
            'status'  => $result['error'] ? 'error' : 'success',
            'message' => $result['message'],
        ], $result['error'] ? 422 : 200);
    }

    /**
     * 📊 Resumen
     */
    public function summary()
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Resumen de salidas',
            'data'    => $this->service->summary(),
        ]);
    }

    /**
     * 📋 Datos para formulario
     */
    public function formData()
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Datos de formulario',
            'data'    => $this->service->formData(),
        ]);
    }
}
