<?php

namespace App\Http\Controllers;

use App\Models\Product_supplier;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ProductSupplierController extends Controller
{
    /**
     * Listar todas las relaciones producto–proveedor.
     */
    public function index()
    {
        return Product_supplier::with(['supplier', 'product'])->get();
    }

    /**
     * Crear una sola relación producto–proveedor.
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
     * Mostrar una relación específica.
     */
    public function show(Product_supplier $product_supplier)
    {
        return $product_supplier->load(['supplier', 'product']);
    }

    /**
     * Actualizar una relación producto–proveedor.
     */
    public function update(Request $request, Product_supplier $product_supplier)
    {
        $validated = $request->validate([
            'unit_cost' => 'nullable|numeric',
            'supplier_reference' => 'nullable|string|max:50'
        ]);

        $product_supplier->update($validated);

        return $product_supplier->load(['supplier', 'product']);
    }

    /**
     * Eliminar una relación producto–proveedor.
     */
    public function destroy(Product_supplier $product_supplier)
    {
        $product_supplier->delete();
        return response()->noContent();
    }

    /**
     * 🔹 Asociar múltiples productos a un proveedor existente.
     */
    public function attachProductsToSupplier(Request $request, $supplierId)
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.unit_cost' => 'nullable|numeric',
            'products.*.supplier_reference' => 'nullable|string|max:50'
        ]);

        $supplier = Supplier::findOrFail($supplierId);

        $data = [];
        foreach ($validated['products'] as $p) {
            $data[$p['product_id']] = [
                'unit_cost' => $p['unit_cost'] ?? null,
                'supplier_reference' => $p['supplier_reference'] ?? null,
            ];
        }

        $supplier->products()->syncWithoutDetaching($data);

        return response()->json([
            'message' => 'Productos asociados correctamente.',
            'supplier' => $supplier->load('products')
        ]);
    }

    /**
     * 🔹 Asociar múltiples proveedores a un producto existente.
     */
    public function attachSuppliersToProduct(Request $request, $productId)
    {
        $validated = $request->validate([
            'suppliers' => 'required|array',
            'suppliers.*.supplier_id' => 'required|exists:suppliers,supplier_id',
            'suppliers.*.unit_cost' => 'nullable|numeric',
            'suppliers.*.supplier_reference' => 'nullable|string|max:50'
        ]);

        $product = Product::findOrFail($productId);

        $data = [];
        foreach ($validated['suppliers'] as $s) {
            $data[$s['supplier_id']] = [
                'unit_cost' => $s['unit_cost'] ?? null,
                'supplier_reference' => $s['supplier_reference'] ?? null,
            ];
        }

        $product->suppliers()->syncWithoutDetaching($data);

        return response()->json([
            'message' => 'Proveedores asociados correctamente.',
            'product' => $product->load('suppliers')
        ]);
    }
}
