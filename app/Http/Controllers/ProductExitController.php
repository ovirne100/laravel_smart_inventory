<?php

namespace App\Http\Controllers;

use App\Models\ProductExit;
use Illuminate\Http\Request;

class ProductExitController extends Controller
{
    public function index()
    {
        return ProductExit::with('user')->get(); // relación en inglés
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'exit_date' => 'required|date',
            'exit_type' => 'required|string|max:20',
        ]);

        $exit = ProductExit::create($validated);

        return response()->json($exit, 201);
    }

    public function show($id)
    {
        $exit = ProductExit::with('user')->findOrFail($id);
        return $exit;
    }

    public function update(Request $request, $id)
    {
        $exit = ProductExit::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'exit_date' => 'sometimes|date',
            'exit_type' => 'sometimes|string|max:20',
        ]);

        $exit->update($validated);

        return response()->json($exit, 200);
    }

    public function destroy($id)
    {
        $exit = ProductExit::findOrFail($id);
        $exit->delete();

        return response()->json(['message' => 'Product exit deleted successfully']);
    }
}
