<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ProductService;

class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * 📄 Listar todos los productos
     */
    public function index(Request $request)
    {
        $data = $this->service->list($request->all());

        return response()->json([
            'status'  => 'success',
            'message' => 'Listado de productos',
            'data'    => $data,
        ]);
    }

    /**
     * ➕ Crear nuevo producto
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'category_id' => 'required|exists:categories,id',
            'reference' => 'nullable|string|max:50',
            'unit_measurement' => 'nullable|string|max:20',
            'batch' => 'required|string|max:50',
            'expiration_date' => 'nullable|date',
        ];

        // Solo validar imagen si se envía
        if ($request->hasFile('image')) {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $validated = $request->validate($rules);

        // Procesar la imagen si existe
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/products'), $imageName);
            $validated['image'] = 'uploads/products/' . $imageName;
        }

        $result = $this->service->create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Producto creado exitosamente',
            'producto' => $result,
        ], 201);
    }

    /**
     * 🔍 Mostrar detalles de un producto
     */
    public function show($id)
    {
        $product = \App\Models\Product::with(['categoria', 'inventory', 'suppliers'])
            ->find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Producto no encontrado',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Detalles del producto',
            'data'    => $product,
        ]);
    }

    /**
     * ✏️ Actualizar producto
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'sometimes|string|max:100',
            'category_id' => 'sometimes|exists:categories,id',
            'reference' => 'sometimes|string|max:50',
            'unit_measurement' => 'sometimes|string|max:20',
            'batch' => 'sometimes|string|max:50',
            'expiration_date' => 'sometimes|date',
        ];

        // Solo validar imagen si se envía
        if ($request->hasFile('image')) {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $validated = $request->validate($rules);

        $product = \App\Models\Product::find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Producto no encontrado',
            ], 404);
        }

        // Procesar la imagen si existe
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }
            
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/products'), $imageName);
            $validated['image'] = 'uploads/products/' . $imageName;
        }

        $result = $this->service->update($product, $validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Producto actualizado exitosamente',
            'data'    => $result,
        ]);
    }

    /**
     * ❌ Eliminar producto
     */
    public function destroy($id)
    {
        $product = \App\Models\Product::find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $this->service->delete($product);

        return response()->json([
            'status'  => 'success',
            'message' => 'Producto eliminado exitosamente',
        ]);
    }

    /**
     * 🔗 Obtener proveedores de un producto
     */
    public function getSuppliers($productId)
    {
        $product = \App\Models\Product::with('suppliers')->find($productId);

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Producto no encontrado',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Proveedores del producto',
            'data'    => $product->suppliers,
        ]);
    }
}