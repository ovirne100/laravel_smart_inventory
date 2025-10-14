<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Product;
use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->service->list($request->all());
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
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'nullable|email|max:100',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:150',
                'tax_id' => 'nullable|string|max:50',
            ]);

            // Mapear campos del frontend a la estructura de la BD
            $data = [
                'name' => $validated['name'],
                'contact_email' => $validated['email'] ?? 'temp@example.com', // Temporal para evitar error
                'phone' => $validated['phone'] ?? '0000000000', // Temporal
                'address' => $validated['address'] ?? 'Dirección temporal', // Temporal
                'tax_id' => 'TEMP-' . time(), // Temporal único
                'status' => 'Active',
            ];

            $supplier = $this->service->create($data);
            return response()->json($supplier, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['products' => function ($q) {
            $q->with('categoria');
        }]);
        return $supplier;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:100',
                'email' => 'nullable|email|max:100',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:150',
                'tax_id' => 'nullable|string|max:50',
            ]);

            $data = [];
            if (array_key_exists('name', $validated)) $data['name'] = $validated['name'];
            if (array_key_exists('email', $validated)) $data['contact_email'] = $validated['email'];
            if (array_key_exists('phone', $validated)) $data['phone'] = $validated['phone'];
            if (array_key_exists('address', $validated)) $data['address'] = $validated['address'];
            if (array_key_exists('tax_id', $validated)) $data['tax_id'] = $validated['tax_id'];
            return $this->service->update($supplier, $data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
 public function destroy($id)
{
    try {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return response()->json([
            'message' => 'Proveedor eliminado correctamente'
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error eliminando proveedor',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // Extra endpoints
    public function products(Supplier $supplier)
    {
        $supplier->load(['products' => function ($q) {
            $q->with('categoria');
        }]);
        return $supplier->products;
    }

    public function attachProducts(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.unit_cost' => 'nullable|numeric',
            'products.*.supplier_reference' => 'nullable|string|max:255',
        ]);

        $attachData = [];
        foreach ($data['products'] as $p) {
            $attachData[$p['product_id']] = [
                'unit_cost' => $p['unit_cost'] ?? null,
                'supplier_reference' => $p['supplier_reference'] ?? null,
            ];
        }
        $supplier->products()->syncWithoutDetaching($attachData);
        return response()->json(['message' => 'Productos asociados correctamente']);
    }

    public function syncProducts(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.unit_cost' => 'nullable|numeric',
            'products.*.supplier_reference' => 'nullable|string|max:255',
        ]);

        $syncData = [];
        foreach ($data['products'] as $p) {
            $syncData[$p['product_id']] = [
                'unit_cost' => $p['unit_cost'] ?? null,
                'supplier_reference' => $p['supplier_reference'] ?? null,
            ];
        }
        $supplier->products()->sync($syncData);
        return response()->json(['message' => 'Products synced']);
    }

    public function detachProduct(Supplier $supplier, Product $product)
    {
        $supplier->products()->detach($product->id);
        return response()->noContent();
    }

    public function getProducts($supplierId)
{
    $supplier = \App\Models\Supplier::with('products')->findOrFail($supplierId);
    return response()->json($supplier->products);
}

}
