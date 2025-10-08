<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // Obtener todas las categorías
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Category::all()
        ]);
    }

    // Inicializar categorías con las del frontend
    public function init(Request $request)
    {
        try {
            $categories = $request->input('categories', []);

            foreach ($categories as $cat) {
                Category::firstOrCreate([
                    'name' => $cat['name']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categorías inicializadas correctamente',
                'data' => Category::all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Sincronizar categorías (similar a init, pero borra y reemplaza)
    public function sync(Request $request)
    {
        try {
            $categories = $request->input('categories', []);

            // Vaciar la tabla antes de insertar
            Category::truncate();

            foreach ($categories as $cat) {
                Category::create([
                    'name' => $cat['name']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categorías sincronizadas correctamente',
                'data' => Category::all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
