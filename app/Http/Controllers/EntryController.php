<?php

namespace App\Http\Controllers;

use App\Services\EntryService;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EntryController extends Controller
{
<<<<<<< HEAD
    protected $entryService;

    public function __construct(EntryService $entryService)
    {
        $this->entryService = $entryService;
    }

=======


public function formData()
{
    return response()->json([
        'productos' => Product::all(),
        'proveedores' => Supplier::all(),
        'usuarios' => User::with('role')->get(),
    ]);
}
>>>>>>> 0ed22cfdc47b44ea2a0de0d18550105196679823
    /**
     * 📄 Listar todas las entradas
     */
    public function index()
    {
        $data = $this->entryService->getAllEntries();

        return response()->json([
            'message' => 'Listado de entradas',
            'data'    => $data
        ]);
    }

    /**
     * ➕ Crear nueva entrada
     */
<<<<<<< HEAD
    public function store(Request $request)
    {
        $entry = $this->entryService->createEntry($request);

        return response()->json([
            'message' => 'Entrada creada exitosamente',
            'data'    => $entry
        ], 201);
=======
public function store(Request $request)
{
    //validar datos
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|numeric|min:1',
        'unit' => 'nullable|string',
        'lot' => 'nullable|string',
        'supplier_id' => 'required|exists:suppliers,id',
        'inventory_id' => 'required|exists:inventories,id',
        'user_id' => 'required|exists:users,id',
        'income_type' => 'required|string|max:50',
    ]);



    $entry = Entry::create([
        'product_id' => $request->product_id,
        'quantity' => $request->quantity,
        'unit' => $request->unit,
        'lot' => $request->lot,
        'supplier_id' => $request->supplier_id,
        'user_id' => Auth::id(), // usuario autenticado o por defecto 1
        'inventory_id' => $request->inventory_id ?? 1, // inventario por defecto
        'income_type'  => $request->income_type,

    ]);

      try {
        $entry = \App\Models\Entry::create($request->all());
        return response()->json(['message' => 'Entrada creada', 'entry' => $entry], 201);
    } catch (\Exception $e) {
        return response()->json([
            'message' => '❌ Error al crear la entrada',
            'error' => $e->getMessage()  // <--- Esto mostrará el error real
        ], 500);
>>>>>>> 0ed22cfdc47b44ea2a0de0d18550105196679823
    }

    return response()->json([
        'message' => 'Entrada creada correctamente',
        'entry' => $entry
    ]);
}


    /**
     * 🔍 Mostrar una entrada
     */
    public function show($id)
    {
        $entry = $this->entryService->getEntryById($id);

        return response()->json([
            'message' => 'Detalles de la entrada',
            'data'    => $entry
        ]);
    }

    /**
     * ✏️ Actualizar una entrada
     */
    public function update(Request $request, $id)
    {
        $entry = $this->entryService->updateEntry($request, $id);

        return response()->json([
            'message' => 'Entrada actualizada exitosamente',
            'data'    => $entry
        ]);
    }

    /**
     * 📊 Resumen de entradas
     */
    public function summary()
    {
        $summary = $this->entryService->getSummary();

        return response()->json($summary);
    }

    /**
     * 🗑️ Eliminar una entrada
     */
    public function destroy($id)
    {
        $this->entryService->deleteEntry($id);

        return response()->json(['message' => 'Entrada eliminada exitosamente']);
    }

    /**
     * 📦 Listas para selects
     */
    public function formData()
    {
        $data = $this->entryService->getFormData();

        return response()->json($data);
    }
}
