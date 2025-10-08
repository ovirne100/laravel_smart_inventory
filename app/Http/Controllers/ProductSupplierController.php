<?php

namespace App\Http\Controllers;

use App\Models\Product_supplier;
use Illuminate\Http\Request;

class ProductSupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Product_supplier::with(['supplier', 'product'])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'product_id' => 'required|exists:products,id',
            'unit_cost' => 'nullable|numeric',
            'supplier_reference' => 'nullable|string|max:50'
        ]);

        $productSupplier = Product_supplier::create($validated);
        return response()->json($productSupplier->load(['supplier', 'product']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product_supplier $product_supplier)
    {
        return $product_supplier->load(['supplier', 'product']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product_supplier $product_supplier)
    {
        $validated = $request->validate([
            'unit_cost' => 'nullable|numeric',
            'supplier_reference' => 'nullable|string|max:50'
        ]);

        $productSupplier->update($validated);
        return $productSupplier->load(['supplier', 'product']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product_supplier $product_supplier)
    {
        $productSupplier->delete();
        return response()->noContent();
    }
}
