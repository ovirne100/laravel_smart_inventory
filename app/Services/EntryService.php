<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class EntryService
{
    /**
     * 📄 Listar todas las entradas
     */
    public function getAllEntries()
    {
        $entries = Entry::with(['product.category', 'supplier'])->get();

        return $entries->map(function ($entry) {
            return [
                'id'                => $entry->id,
                'producto'          => $entry->product->name ?? 'Producto desconocido',
                'categoria'         => $entry->product->category->name ?? 'Sin categoría',
                'proveedor'         => $entry->supplier->name ?? 'Desconocido',
                'fecha'             => $entry->created_at ? $entry->created_at->format('d/m/Y') : null,
                'lote'              => $entry->lot ?? '',
                'cantidad'          => $entry->quantity . ' ' . ($entry->unit ?? ''),
                'ubicacion_interna' => $entry->ubicacion_interna,
                'stock'             => $entry->stock,
                'stock_min'         => $entry->stock_min,
            ];
        });
    }

    /**
     * ➕ Crear nueva entrada
     */
    public function createEntry(array $data)
    {
        return DB::transaction(function () use ($data) {
            $entry = Entry::create($data);
            return $entry->load(['product', 'supplier']);
        });
    }

    /**
     * 🔍 Obtener una entrada por ID
     */
    public function getEntryById($id)
    {
        $entry = Entry::with(['product.category', 'supplier'])->findOrFail($id);

        return [
            'id'                => $entry->id,
            'producto'          => $entry->product->name ?? 'Producto desconocido',
            'categoria'         => $entry->product->category->name ?? 'Sin categoría',
            'proveedor'         => $entry->supplier->name ?? 'Desconocido',
            'fecha'             => $entry->created_at ? $entry->created_at->format('d/m/Y') : null,
            'lote'              => $entry->lot ?? '',
            'cantidad'          => $entry->quantity . ' ' . ($entry->unit ?? ''),
            'ubicacion_interna' => $entry->ubicacion_interna,
            'stock'             => $entry->stock,
            'stock_min'         => $entry->stock_min,
        ];
    }

    /**
     * ✏️ Actualizar una entrada
     */
    public function updateEntry(array $data, $id)
    {
        $entry = Entry::findOrFail($id);

        return DB::transaction(function () use ($entry, $data) {
            $entry->update($data);

            return $entry->load(['product', 'supplier']);
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
        $entry->delete();
    }

    /**
     * 📦 Datos para selects (productos, proveedores)
     */
    public function getFormData()
    {
        return [
            'productos'   => Product::select('id', 'name')->get(),
            'proveedores' => Supplier::select('id', 'name')->get(),
        ];
    }
}
