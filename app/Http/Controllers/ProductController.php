<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Mostrar listado de productos con filtros opcionales
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'status', 'min_price', 'max_price']);
        $productos = $this->productService->getAll($filters);

        return response()->json($productos);
    }

    /**
     * Mostrar un producto específico
     */
    public function show(Product $product)
    {
        $product->load(['categoria', 'inventory']);
        return response()->json($product);
    }

    /**
     * Crear un nuevo producto junto con su inventario inicial
     */

    public function store(Request $request)
    {
        // Validar los datos recibidos (permitir opcionales como en el frontend)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'unit_measurement' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:categories,id',
            'price' => 'nullable|numeric',
            'quantity' => 'nullable|integer',
            'min_stock' => 'nullable|integer',
            'location' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|date',
            'image' => 'nullable|file|image|max:2048',
        ]);

        // Subida de imagen (si existe)
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads', 'public');
            $validated['image'] = $path;
        }

        // Crear producto
        $product = Product::create($validated);

        // Crear inventario automáticamente
        \App\Models\Inventory::create([
            'product_id' => $product->id,
            'quantity' => $validated['quantity'] ?? 0,
            'min_stock' => $validated['min_stock'] ?? 0,
            'location' => $validated['location'] ?? 'Sin ubicación',
        ]);

        Log::info('✅ Producto creado correctamente', ['id' => $product->id]);

        return response()->json([
            'message' => 'Producto creado exitosamente',
            'product' => $product,
        ], 201);

    } catch (\Exception $e) {
        Log::error('❌ Error al crear producto: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}





    /**
     * Actualizar producto existente
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'reference' => 'sometimes|string|max:100|unique:products,reference,' . $product->id,
            'unit_measurement' => 'sometimes|string|max:50',
            'batch' => 'sometimes|string|max:100',
            'category_id' => 'sometimes|exists:categories,id',
            'expiration_date' => 'nullable|date',
            'image' => 'nullable|file|image|max:2048',
        ]);

        // 📸 Actualizar imagen si se envía una nueva
        if ($request->hasFile('image')) {
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }

            $path = $request->file('image')->store('uploads/products', 'public');
            $validated['image'] = 'storage/' . $path;
        }

        $product = $this->productService->update($product, $validated);

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'data' => $product,
        ]);
    }

    /**
     * Eliminar producto
     */
    public function destroy(Product $product)
    {
        if ($product->image && file_exists(public_path($product->image))) {
            unlink(public_path($product->image));
        }

        $this->productService->delete($product);

        return response()->json([
            'message' => 'Producto eliminado correctamente.'
        ]);
    }

    public function getSuppliers($productId)
{
    $product = \App\Models\Product::with('suppliers')->findOrFail($productId);
    return response()->json($product->suppliers);
}

}
