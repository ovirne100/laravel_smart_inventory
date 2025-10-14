<?php

namespace App\Services;

use App\Models\Output;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OutputService
{
    /**
     * 📄 Listar todas las salidas
     */
    public function listAll()
    {
        $outputs = Output::with(['product.category', 'user'])->get();

        return $outputs->map(function ($output) {
            return [
                'id' => $output->id,
                'producto' => $output->product->name ?? 'Producto desconocido',
                'categoria' => $output->product->category->name ?? 'Sin categoría',
                'usuario' => $output->user->name ?? 'Desconocido',
                'fecha' => $output->created_at ? $output->created_at->format('d/m/Y') : null,
                'lote' => $output->lot ?? '',
                'cantidad' => $output->quantity . ' ' . ($output->unit ?? ''),
                'inventory_id' => $output->inventory_id,
            ];
        });
    }

    /**
     * ➕ Crear nueva salida
     */
    public function create(array $validated)
    {
        return DB::transaction(function () use ($validated) {

            // 🔹 Buscar inventario asociado o deducirlo por producto
            $inventory = null;

            if (isset($validated['inventory_id'])) {
                $inventory = Inventory::find($validated['inventory_id']);
            } else {
                $inventory = Inventory::where('product_id', $validated['product_id'])->first();
            }

            if (!$inventory) {
                return [
                    'error' => true,
                    'message' => 'No se encontró un inventario válido para este producto.'
                ];
            }

            // 🔹 Verificar stock suficiente
            if ($validated['quantity'] > $inventory->stock) {
                return [
                    'error' => true,
                    'message' => 'No hay suficiente stock para esta salida.'
                ];
            }

            // 🔹 Crear la salida
            $output = Output::create([
                ...$validated,
                'inventory_id' => $inventory->id,
            ]);

            // 🔹 Actualizar stock
            $inventory->stock -= $validated['quantity'];
            $inventory->save();

            return [
                'error' => false,
                'data' => $output->load(['product', 'user'])
            ];
        });
    }

    /**
     * 🔍 Mostrar una salida
     */
    public function find($id)
    {
        $output = Output::with(['product.category', 'user'])->findOrFail($id);

        return [
            'id' => $output->id,
            'producto' => $output->product->name ?? 'Producto desconocido',
            'categoria' => $output->product->category->name ?? 'Sin categoría',
            'usuario' => $output->user->name ?? 'Desconocido',
            'fecha' => $output->created_at ? $output->created_at->format('d/m/Y') : null,
            'lote' => $output->lot ?? '',
            'cantidad' => $output->quantity . ' ' . ($output->unit ?? ''),
        ];
    }

    /**
     * ✏️ Actualizar una salida
     */
    public function update($id, array $validated)
    {
        $output = Output::findOrFail($id);

        return DB::transaction(function () use ($output, $validated) {
            $inventory = Inventory::findOrFail($output->inventory_id);

            if (isset($validated['quantity']) && $validated['quantity'] != $output->quantity) {
                $diff = $validated['quantity'] - $output->quantity;

                if ($diff > 0 && $diff > $inventory->stock) {
                    return [
                        'error' => true,
                        'message' => 'No hay suficiente stock para aumentar la cantidad.'
                    ];
                }

                $inventory->stock -= $diff;
                $inventory->save();
            }

            $output->update($validated);

            return [
                'error' => false,
                'data' => $output->load(['product', 'user'])
            ];
        });
    }

    /**
     * 📊 Resumen de salidas
     */
    public function summary()
    {
        return [
            'total_outputs' => Output::count(),
            'total_quantity' => Output::sum('quantity'),
            'last_output_date' => optional(Output::latest()->value('created_at'))->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 🗑️ Eliminar una salida
     */
    public function delete($id)
    {
        $output = Output::findOrFail($id);

        return DB::transaction(function () use ($output) {
            $inventory = Inventory::findOrFail($output->inventory_id);
            $inventory->stock += $output->quantity;
            $inventory->save();

            $output->delete();

            return true;
        });
    }

    /**
     * 📦 Datos para formularios
     */
    public function formData()
    {
        return [
            'productos' => Product::select('id', 'name')->get(),
            'usuarios' => User::select('id', 'name')->get(),
        ];
    }
}
