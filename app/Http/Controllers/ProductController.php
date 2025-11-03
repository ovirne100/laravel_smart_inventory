<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Supplier;

class ProductController extends Controller
{
    /**
     * 📄 Listar todos los productos
     */
    public function index(Request $request)
{
    try {
        $search = $request->query('search');
        $perPage = (int)($request->query('perPage', 20));
        $page = (int)($request->query('page', 1));

        $query = Product::with(['categoria', 'inventory', 'suppliers', ]);


        // Si tu relación con categoría se llama "categoria", usa esa.


        // 🔍 Filtro de búsqueda
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        // 📄 Paginación
        $products = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Listado de productos obtenido correctamente',
            'data' => $products
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error al obtener productos',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * ➕ Crear un producto
     */
public function store(Request $request)
{
    // 1️⃣ Validar datos básicos
    $request->validate([
        'name' => 'required|string|max:255',
        'category_id' => 'required|integer|exists:categories,id',
        'reference' => 'nullable|string|max:255',
        'unit_measurement' => 'required|string|max:50',
        'batch' => 'required|string|max:100', // 🔹 obligatorio para validar lote
        'expiration_date' => 'nullable|date',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    // 2️⃣ Verificar si ya existe un producto con el mismo nombre y lote
    $loteExistente = Product::where('name', $request->name)
        ->where('batch', $request->batch)
        ->exists();

    if ($loteExistente) {
        return response()->json([
            'message' => '⚠️ Ya existe un producto con el mismo nombre y lote. Cada producto debe tener un lote único.',
        ], 422);
    }

    // 3️⃣ Crear instancia del producto
    $product = new Product();
    $product->name = $request->name;
    $product->category_id = $request->category_id;
    $product->reference = $request->reference;
    $product->unit_measurement = $request->unit_measurement;
    $product->batch = $request->batch;
    $product->expiration_date = $request->expiration_date;

    // 4️⃣ Guardar imagen si existe
    if ($request->hasFile('image')) {
        $product->image = $request->file('image')->store('products', 'public');
    }

    // 5️⃣ Guardar en base de datos
    $product->save();

    // 6️⃣ Respuesta exitosa
    return response()->json([
        'message' => '✅ Producto guardado exitosamente',
        'product' => $product
    ], 201);
}


    /**
     * 🔍 Mostrar un producto
     */
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detalle del producto',
            'data' => $product,
        ]);
    }

    /**
     * ✏️ Actualizar un producto
     */
public function update(Request $request, $id)
{
    $product = Product::find($id);

    if (!$product) {
        return response()->json([
            'status' => 'error',
            'message' => 'Producto no encontrado',
        ], 404);
    }

    // 🔹 Validar campos
    $validated = $request->validate([
        'name' => 'nullable|string|max:255',
        'category_id' => 'nullable|exists:categories,id',
        'reference' => 'nullable|string|max:100',
        'unit_measurement' => 'nullable|string|max:50',
        'batch' => 'nullable|string|max:50',
        'expiration_date' => 'nullable|date',
        'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    // 🔹 Validar lote único (manteniendo tu lógica)
    if (!empty($validated['name']) || !empty($validated['batch'])) {
        $loteExistente = Product::where('id', '!=', $product->id)
            ->where('name', $validated['name'] ?? $product->name)
            ->where('batch', $validated['batch'] ?? $product->batch)
            ->exists();

        if ($loteExistente) {
            return response()->json([
                'status' => 'error',
                'message' => '⚠️ Ya existe otro producto con el mismo nombre y lote. Cada producto debe tener un lote único.',
            ], 422);
        }
    }

    // 🔹 Subir imagen si hay una nueva
    if ($request->hasFile('image')) {
        $validated['image'] = $request->file('image')->store('products', 'public');
    }

    // 🔹 Actualizar producto solo con los campos enviados
    $product->fill($validated);

    if ($product->isDirty()) { // Guarda solo si hay cambios
        $product->save();
    }

    // 🔹 Devolver producto actualizado desde DB real
    return response()->json([
        'status' => 'success',
        'message' => '✅ Producto actualizado correctamente',
        'data' => $product->fresh(),
    ]);
}


    /**
     * ❌ Eliminar un producto
     */
    public function destroy($id)
{
    try {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => '❌ Producto no encontrado',
            ], 404);
        }

        // 🔹 Verificar si el producto tiene relaciones que impiden borrarlo
        if ($product->suppliers()->exists() || $product->inventory()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => '⚠️ No se puede eliminar el producto porque está asociado a inventario o proveedores.',
            ], 409); // 409 = conflicto
        }


        // 🔹 Eliminar producto
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => '✅ Producto eliminado correctamente',
        ]);

    } catch (\Throwable $th) {
        return response()->json([
            'status' => 'error',
            'message' => '⚠️ Error interno al eliminar el producto: ' . $th->getMessage(),
        ], 500);
    }
}


    /**
     * 🔗 Obtener todos los proveedores de un producto
     */
    public function getSuppliers($productId, Request $request)
    {
        // Buscar el producto
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado',
            ], 404);
        }

        // Opcional: filtros de búsqueda o paginación
        $search = $request->query('search');
        $perPage = (int) ($request->query('per_page', 50));

        $query = $product->suppliers()->withPivot('unit_cost', 'supplier_reference');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $suppliers = $perPage > 0 ? $query->paginate($perPage) : $query->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Proveedores del producto obtenidos correctamente',
            'data'    => $suppliers,
        ]);
    }
}
