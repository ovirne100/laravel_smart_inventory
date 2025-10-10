<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntryService
{
    /**
     * 📄 Listar todas las entradas
     */
    public function getAllEntries()
    {
        $entries = Entry::with(['product.category', 'user', 'supplier'])->get();

        return $entries->map(function ($entry) {
            return [
                'id'           => $entry->id,
                'producto'     => $entry->product->name ?? 'Producto desconocido',
                'categoria'    => $entry->product->category->name ?? 'Sin categoría',
                'usuario'      => $entry->user->name ?? 'Desconocido',
                'proveedor'    => $entry->supplier->name ?? 'Desconocido',
                'fecha'        => $entry->created_at ? $entry->created_at->format('d/m/Y') : null,
                'lote'         => $entry->lot ?? '',
                'cantidad'     => $entry->quantity . ' ' . ($entry->unit ?? ''),
                'inventory_id' => $entry->inventory_id,
            ];
        });
    }

    /**
     * ➕ Crear nueva entrada
     */
    public function createEntry(Request $request)
    {
        $validated = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'quantity'     => 'required|integer|min:1',
            'unit'         => 'nullable|string|max:20',
            'lot'          => 'nullable|string|max:50',
            'supplier_id'  => 'required|exists:suppliers,id',
            'user_id'      => 'required|exists:users,id',
            'inventory_id' => 'required|exists:inventories,id',
        ]);

        return DB::transaction(function () use ($validated) {
            $entry = Entry::create($validated);

            // Aumentar stock
            $inventory = Inventory::findOrFail($validated['inventory_id']);
            $inventory->stock += $validated['quantity'];
            $inventory->save();

            return $entry->load(['product', 'supplier', 'user']);
        });
    }

    /**
     * 🔍 Obtener una entrada por ID
     */
    public function getEntryById($id)
    {
        $entry = Entry::with(['product.category', 'supplier', 'user'])->findOrFail($id);

        return [
            'id'         => $entry->id,
            'producto'   => $entry->product->name ?? 'Producto desconocido',
            'categoria'  => $entry->product->category->name ?? 'Sin categoría',
            'proveedor'  => $entry->supplier->name ?? 'Desconocido',
            'usuario'    => $entry->user->name ?? 'Desconocido',
            'fecha'      => $entry->created_at ? $entry->created_at->format('d/m/Y') : null,
            'lote'       => $entry->lot ?? '',
            'cantidad'   => $entry->quantity . ' ' . ($entry->unit ?? ''),
        ];
    }

    /**
     * ✏️ Actualizar una entrada
     */
    public function updateEntry(Request $request, $id)
    {
        $entry = Entry::findOrFail($id);

        $validated = $request->validate([
            'product_id'   => 'sometimes|exists:products,id',
            'quantity'     => 'sometimes|integer|min:1',
            'unit'         => 'sometimes|string|max:20',
            'lot'          => 'sometimes|string|max:50',
            'supplier_id'  => 'sometimes|exists:suppliers,id',
            'user_id'      => 'sometimes|exists:users,id',
            'inventory_id' => 'sometimes|exists:inventories,id',
        ]);

        return DB::transaction(function () use ($entry, $validated) {
            // Ajustar stock si cambia cantidad
            if (isset($validated['quantity']) && $validated['quantity'] != $entry->quantity) {
                $diff = $validated['quantity'] - $entry->quantity;
                $inventory = Inventory::findOrFail($entry->inventory_id);
                $inventory->stock += $diff;
                $inventory->save();
            }

            $entry->update($validated);

            return $entry->load(['product', 'supplier', 'user']);
        });
    }

    /**
     * 📊 Resumen de entradas
     */
    public function getSummary()
    {
        $count = Entry::count();
        $total = Entry::sum('quantity');
        $last  = Entry::latest()->value('created_at');

        return [
            'total_entries'   => $count,
            'total_quantity'  => $total,
            'last_entry_date' => $last ? $last->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * 🗑️ Eliminar una entrada
     */
    public function deleteEntry($id)
    {
        $entry = Entry::findOrFail($id);

        return DB::transaction(function () use ($entry) {
            $inventory = Inventory::findOrFail($entry->inventory_id);
            $inventory->stock -= $entry->quantity;
            $inventory->save();

            $entry->delete();
        });
    }

    /**
     * 📦 Datos para selects (productos, usuarios, proveedores)
     */
    public function getFormData()
    {
        return [
            'productos'   => Product::select('id', 'name')->get(),
            'usuarios'    => User::select('id', 'name')->get(),
            'proveedores' => Supplier::select('id', 'name')->get(),
        ];
    }
}
