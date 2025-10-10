<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExitDetailService;
use App\Models\Product;

class ExitDetailController extends Controller
{
public function formData()
{
    return response()->json([
        'productos' => Product::all()
    ]);
}

    protected $service;

    public function __construct(ExitDetailService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->getAll());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'idProducto' => 'required|exists:products,idProducto',
            'idSalida'   => 'required|exists:product_exits,idSalida',
            'cantidad'   => 'required|integer|min:1',
            'destino'    => 'required|string|max:50',
        ]);

        return response()->json($this->service->create($validated), 201);
    }

    public function show($id)
    {
        return response()->json($this->service->getById($id));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'idProducto' => 'sometimes|exists:products,idProducto',
            'idSalida'   => 'sometimes|exists:product_exits,idSalida',
            'cantidad'   => 'sometimes|integer|min:1',
            'destino'    => 'sometimes|string|max:50',
        ]);

        return response()->json($this->service->update($id, $validated));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'ExitDetail eliminado correctamente']);
    }
}
