<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EntryController
{


public function formData()
{
    return response()->json([
        'productos' => Product::all(),
        'proveedores' => Supplier::all(),
        'usuarios' => User::with('role')->get(),
    ]);
}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    //validar datos
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|numeric|min:1',
        'unit' => 'nullable|string',
        'lot' => 'nullable|string',
        'supplier_id' => 'required|exists:suppliers,id',
        'inventory_id' => 'required|exists:inventories,id',
        'user_id' => 'required|exists:users,id',
        'income_type' => 'required|string|max:50',
    ]);



    $entry = Entry::create([
        'product_id' => $request->product_id,
        'quantity' => $request->quantity,
        'unit' => $request->unit,
        'lot' => $request->lot,
        'supplier_id' => $request->supplier_id,
        'user_id' => Auth::id(), // usuario autenticado o por defecto 1
        'inventory_id' => $request->inventory_id ?? 1, // inventario por defecto
        'income_type'  => $request->income_type,

    ]);

      try {
        $entry = \App\Models\Entry::create($request->all());
        return response()->json(['message' => 'Entrada creada', 'entry' => $entry], 201);
    } catch (\Exception $e) {
        return response()->json([
            'message' => '❌ Error al crear la entrada',
            'error' => $e->getMessage()  // <--- Esto mostrará el error real
        ], 500);
    }

    return response()->json([
        'message' => 'Entrada creada correctamente',
        'entry' => $entry
    ]);
}


    /**
     * Display the specified resource.
     */
    public function show(Entry $entry)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entry $entry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Entry $entry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Entry $entry)
    {
        //
    }
}
