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
     * Listar proveedores
     */
    public function index(Request $request)
    {
        return $this->service->list($request->all());
    }

    /**
     * Crear nuevo proveedor
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

            $data = [
                'name' => $validated['name'],
                'contact_email' => $validated['email'] ?? 'temp@example.com',
                'phone' => $validated['phone'] ?? '0000000000',
                'address' => $validated['address'] ?? 'Dirección temporal',
                'tax_id' => $validated['tax_id'] ?? 'TEMP-' . time(),
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
     * Mostrar proveedor específico con productos y categorías
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['products.categoria']);
        return $supplier;
    }

    /**
     * Actualizar proveedor
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
            if (isset($validated['name'])) $data['name'] = $validated['name'];
            if (isset($validated['email'])) $data['contact_email'] = $validated['email'];
            if (isset($validated['phone'])) $data['phone'] = $validated['phone'];
            if (isset($validated['address'])) $data['address'] = $validated['address'];
            if (isset($validated['tax_id'])) $data['tax_id'] = $validated['tax_id'];

            return $this->service->update($supplier, $data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar proveedor
     */
    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            return response()->json(['message' => 'Proveedor eliminado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error eliminando proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Productos asociados a un proveedor
     */
    public function products(Supplier $supplier)
    {
        $supplier->load(['products.categoria']);
        return $supplier->products;
    }

    /**
     * Asociar productos sin eliminar existentes
     */
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

    /**
     * Sincronizar productos (elimina los que no están en el array)
     */
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
        return response()->json(['message' => 'Productos sincronizados correctamente']);
    }

    /**
     * Desvincular un producto
     */
    public function detachProduct($supplierId, $productId)
    {
        $supplier = Supplier::findOrFail($supplierId);
        $supplier->products()->detach($productId);

        return response()->json(['message' => 'Producto desvinculado correctamente']);
    }

    /**
     * Obtener productos de un proveedor
     */
    public function getProducts($supplierId)
    {
        $supplier = Supplier::with('products')->findOrFail($supplierId);
        return response()->json($supplier->products);
    }
}
