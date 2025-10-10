<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


use App\Services\ProductService;

class ProductController extends Controller
{
    protected $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

public function index(Request $request)
{
    $productos = $this->service->getAll($request->all());

    // Cargar la relación para cada producto del paginador
    $productos->getCollection()->load('categoria');

    return response()->json($productos);
}


    public function store(Request $request)
    {
        // Validar los datos recibidos (permitir opcionales como en el frontend)
        $validated = $request->validate([
            'name' => 'required|string',
            'category_id' => 'nullable|integer',
            'reference' => 'nullable|string',
            'unit_measurement' => 'nullable|string',
            'batch' => 'required|string',
            'expiration_date' => 'nullable|date',
           'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // validación de imagen
        ]);

    // Crear instancia de Product
    $producto = new Product();
    $producto->name = $request->name;
    $producto->reference = $request->reference;
    $producto->unit_measurement = $request->unit_measurement;
    $producto->batch = $request->batch;
    $producto->expiration_date = $request->expiration_date;
    $producto->categoria_id = $request->category_id;

           // Guardar imagen si existe
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension(); // nombre único
        $image->move(public_path('uploads/products'), $imageName);
       $validated['image'] = 'uploads/products/' . $imageName; // ruta relativa
    }

        // Crear el producto usando el servicio
        $producto = $this->service->create($validated);
        $producto->save();

        // Retornar respuesta
        return response()->json([
            'message' => 'Producto creado con éxito',
            'data' => $producto,
            'producto' => $producto
        ], 201);
    }

    public function update(Request $r, Product $product)
    {
        $p = $this->service->update($product, $r->all());
        return response()->json($p);
    }

    public function destroy(Product $product)
    {
        $this->service->delete($product);
        return response()->json(['message' => 'deleted']);
    }

    public function getSuppliers($productId)
{
    $product = \App\Models\Product::with('suppliers')->findOrFail($productId);
    return response()->json($product->suppliers);
}

}
