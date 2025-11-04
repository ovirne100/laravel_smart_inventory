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
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? 'Sin teléfono',
                'address' => $validated['address'] ?? 'Sin dirección',
                'tax_id' => $validated['tax_id'] ?? 'TEMP-' . time(),
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
            if (array_key_exists('email', $validated)) $data['email'] = $validated['email'];
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
        try {
            // Cargar productos con pivot (sin cargar category para evitar errores)
            $supplier->load('products');
            
            // Transformar productos manualmente para evitar problemas de serialización
            $productsArray = [];
            
            foreach ($supplier->products as $product) {
                $productData = [
                    'product_id' => $product->id,
                    'id' => $product->id,
                    'name' => $product->name ?? '',
                    'reference' => $product->reference ?? null,
                    'category_id' => $product->category_id ?? null,
                ];
                
                // Agregar categoría si existe (cargar manualmente si es necesario)
                if ($product->category_id) {
                    try {
                        $category = $product->category;
                        if ($category) {
                            $productData['categoria'] = [
                                'id' => $category->id,
                                'name' => $category->name ?? ''
                            ];
                        }
                    } catch (\Exception $e) {
                        // Si hay error cargando category, continuar sin ella
                        \Log::warning('No se pudo cargar category para producto', [
                            'product_id' => $product->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Agregar pivot si existe
                if ($product->pivot) {
                    $productData['pivot'] = [
                        'unit_cost' => $product->pivot->unit_cost ?? null,
                        'supplier_reference' => $product->pivot->supplier_reference ?? null,
                    ];
                }
                
                $productsArray[] = $productData;
            }
            
            return response()->json($productsArray);
        } catch (\Exception $e) {
            \Log::error('Error en products() del SupplierController', [
                'supplier_id' => $supplier->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener productos del proveedor',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function attachProducts(Request $request, Supplier $supplier)
    {
        try {
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
            
            return response()->json([
                'status' => 'success',
                'message' => 'Productos asociados correctamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al asociar productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function syncProducts(Request $request, Supplier $supplier)
    {
        try {
            // Validar que products sea un array
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
            
            $supplier->products()->syncWithoutDetaching($syncData);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Productos asociados correctamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al asociar productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function detachProduct(Supplier $supplier, Product $product)
    {
        $supplier->products()->detach($product->id);
        return response()->noContent();
    }

    public function getProducts($supplier)
    {
        try {
            // Manejar tanto model binding como ID directo
            if (is_numeric($supplier)) {
                $supplierModel = Supplier::findOrFail($supplier);
            } elseif ($supplier instanceof Supplier) {
                $supplierModel = $supplier;
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Proveedor no válido'
                ], 400);
            }
            
            // Cargar productos con pivot (sin cargar category para evitar errores)
            $supplierModel->load('products');
            
            // Transformar productos manualmente para evitar problemas de serialización
            $productsArray = [];
            
            foreach ($supplierModel->products as $product) {
                $productData = [
                    'product_id' => $product->id,
                    'id' => $product->id,
                    'name' => $product->name ?? '',
                    'reference' => $product->reference ?? null,
                    'category_id' => $product->category_id ?? null,
                ];
                
                // Agregar categoría si existe (cargar manualmente si es necesario)
                if ($product->category_id) {
                    try {
                        // Intentar cargar category si no está cargada
                        if (!$product->relationLoaded('category')) {
                            $product->load('category');
                        }
                        
                        if ($product->category) {
                            $productData['categoria'] = [
                                'id' => $product->category->id,
                                'name' => $product->category->name ?? ''
                            ];
                        }
                    } catch (\Exception $e) {
                        // Si hay error cargando category, continuar sin ella
                        \Log::warning('No se pudo cargar category para producto', [
                            'product_id' => $product->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Agregar pivot si existe
                if ($product->pivot) {
                    $productData['pivot'] = [
                        'unit_cost' => $product->pivot->unit_cost ?? null,
                        'supplier_reference' => $product->pivot->supplier_reference ?? null,
                    ];
                }
                
                $productsArray[] = $productData;
            }
            
            return response()->json($productsArray);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proveedor no encontrado',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error en getProducts() del SupplierController', [
                'supplier' => is_numeric($supplier) ? $supplier : ($supplier instanceof Supplier ? $supplier->id : 'unknown'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener productos del proveedor',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
}

}
