<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\OutputService;
use App\Models\Output;
use App\Models\Inventory;

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
    $outputs = Output::with(['product:id,name', 'inventory:id,stock', 'user:id,name'])
        ->orderByDesc('created_at')
        ->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Lista de salidas obtenida correctamente.',
        'data' => $outputs
    ]);
}


    /**
     * ➕ Crear nueva salida
     */
 public function store(Request $request)
{
    $validated = $request->validate([
        'inventory_id' => 'required|exists:inventories,id',
        'quantity'     => 'required|numeric|min:1',
        'unit'         => 'nullable|string|max:20',
        'lot'          => 'nullable|string|max:50',
        'motivo'       => 'nullable|string|max:255',
    ]);

    $inventory = Inventory::with('product')->find($validated['inventory_id']);

    if (!$inventory) {
        return response()->json([
            'status' => 'error',
            'message' => 'Inventario no encontrado.'
        ], 404);
    }

    // 🔍 Verificar stock disponible
    if ($inventory->stock < $validated['quantity']) {
        return response()->json([
            'status' => 'error',
            'message' => 'La cantidad solicitada excede el stock disponible.'
        ], 422);
    }

    // 🔹 Crear salida con todos los campos requeridos
    $output = Output::create([
        'product_id'   => $inventory->product_id, // 👈 importante
        'inventory_id' => $inventory->id,
        'quantity'     => $validated['quantity'],
        'unit'         => $validated['unit'] ?? $inventory->unit,
        'lot'          => $validated['lot'] ?? $inventory->lot,
        'motivo'       => $validated['motivo'],
        'user_id'      => Auth::id(), // 👈 registra el usuario
    ]);

    // 🔹 Descontar del inventario
    $inventory->stock -= $validated['quantity'];
    $inventory->save();

    return response()->json([
        'status' => 'success',
        'message' => '✅ Salida registrada correctamente.',
        'data' => $output->load(['product', 'inventory', 'user']),
    ], 201);
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
            'inventory_id' => 'sometimes|exists:inventories,id',
            'quantity'     => 'sometimes|numeric|min:1',
            'unit'         => 'sometimes|string|max:20',
            'lot'          => 'sometimes|string|max:50',
            'motivo'       => 'sometimes|string|max:255',
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
