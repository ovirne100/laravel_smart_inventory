<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * 📋 Listar todos los almacenes
     */
    public function index()
    {
        return response()->json(Warehouse::all());
    }

    /**
     * 🏗️ Crear un nuevo almacén
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:50',
            'address'  => 'required|string|max:100',
            'capacity' => 'required|string|max:100',
        ]);

        $warehouse = Warehouse::create($validated);

        return response()->json([
            'message' => 'Warehouse creado exitosamente',
            'data'    => $warehouse
        ], 201);
    }

    /**
     * 🔍 Mostrar un almacén específico
     */
    public function show(Warehouse $warehouse)
    {
        return response()->json($warehouse);
    }

    /**
     * ✏️ Actualizar un almacén
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name'     => 'sometimes|string|max:50',
            'address'  => 'sometimes|string|max:100',
            'capacity' => 'sometimes|string|max:100',
        ]);

        $warehouse->update($validated);

        return response()->json([
            'message' => 'Warehouse actualizado exitosamente',
            'data'    => $warehouse
        ]);
    }

    /**
     * 🗑️ Eliminar un almacén
     */
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return response()->json([
            'message' => 'Warehouse eliminado exitosamente'
        ]);
    }
}
