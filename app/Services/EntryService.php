<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Inventory;
use App\Services\AlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntryService
{
    protected ?AlertService $alertService;

    public function __construct(AlertService $alertService = null)
    {
        $this->alertService = $alertService;
    }

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
                'fecha'             => $entry->created_at?->format('d/m/Y'),
                'lote'              => $entry->lot ?? '',
                'cantidad'          => $entry->quantity . ' ' . ($entry->unit ?? ''),
                'ubicacion_interna' => $entry->ubicacion_interna,
                'min_stock'         => $entry->min_stock,
            ];
        });
    }

    /**
     * ➕ Crear nueva entrada y gestionar inventario automáticamente
     */
    public function createEntryWithInventoryAndUser(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;
            $data['stock'] = $data['quantity'] ?? 0;

            Log::info('📥 Datos recibidos en createEntryWithInventoryAndUser:', $data);

            // Crear entrada
            $entry = Entry::create($data);
            Log::info("✅ Entrada creada con ID {$entry->id}");

            // Buscar inventario existente (por producto + lote)
            $inventory = Inventory::where('product_id', $entry->product_id)
                                  ->where('lot', $entry->lot)
                                  ->first();

            if ($inventory) {
                // Actualizar stock existente
                $inventory->stock += $entry->quantity;
                if (isset($entry->min_stock) && $entry->min_stock > 0) {
                    $inventory->min_stock = $entry->min_stock;
                }
                $inventory->save();

                Log::info("📦 Inventario actualizado: producto ID {$entry->product_id}, stock {$inventory->stock}");
            } else {
                // Crear nuevo inventario
                $inventory = Inventory::create([
                    'product_id'        => $entry->product_id,
                    'lot'               => $entry->lot,
                    'stock'             => $entry->quantity,
                    'min_stock'         => $entry->min_stock ?? 0,
                    'ubicacion_interna' => $entry->ubicacion_interna,
                    'user_id'           => $userId,
                ]);
                Log::info("🆕 Nuevo inventario creado para producto ID {$entry->product_id}");
            }

            // Verificar alertas automáticas
            if ($this->alertService) {
                $this->alertService->checkStock($inventory);
            }

            return $entry->load(['product', 'supplier']);
        });
    }

    /**
     * 🔍 Obtener una entrada por ID
     */
    public function getEntryById(int $id)
    {
        $entry = Entry::with(['product.category', 'supplier'])->findOrFail($id);

        return [
            'id'                => $entry->id,
            'producto'          => $entry->product->name ?? 'Producto desconocido',
            'categoria'         => $entry->product->category->name ?? 'Sin categoría',
            'proveedor'         => $entry->supplier->name ?? 'Desconocido',
            'fecha'             => $entry->created_at?->format('d/m/Y'),
            'lote'              => $entry->lot ?? '',
            'cantidad'          => $entry->quantity . ' ' . ($entry->unit ?? ''),
            'ubicacion_interna' => $entry->ubicacion_interna,
            'min_stock'         => $entry->min_stock,
        ];
    }

    /**
     * ✏️ Actualizar una entrada
     */
    public function updateEntry(array $data, int $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $entry = Entry::findOrFail($id);
            $oldQuantity = $entry->quantity;
            $entry->update($data);

            // Actualizar inventario si cambió cantidad o lote
            $inventory = Inventory::where('product_id', $entry->product_id)
                                  ->where('lot', $entry->lot)
                                  ->first();

            if ($inventory && isset($data['quantity'])) {
                $inventory->stock += $data['quantity'] - $oldQuantity;
                $inventory->save();

                if ($this->alertService) {
                    $this->alertService->checkStock($inventory);
                }
            }

            return $entry->load(['product', 'supplier']);
        });
    }

    /**
     * 🗑️ Eliminar una entrada
     */
    public function deleteEntry(int $id): void
    {
        DB::transaction(function () use ($id) {
            $entry = Entry::findOrFail($id);

            // Ajustar inventario
            $inventory = Inventory::where('product_id', $entry->product_id)
                                  ->where('lot', $entry->lot)
                                  ->first();

            if ($inventory) {
                $inventory->stock -= $entry->quantity;
                if ($inventory->stock < 0) {
                    $inventory->stock = 0;
                }
                $inventory->save();

                if ($this->alertService) {
                    $this->alertService->checkStock($inventory);
                }
            }

            $entry->delete();
            Log::info("🗑️ Entrada ID {$id} eliminada correctamente.");
        });
    }

    /**
     * 📊 Resumen de entradas (para frontend)
     */
    public function getSummary(): array
    {
        $entries = Entry::select(DB::raw('COUNT(id) as total_entries'), DB::raw('SUM(quantity) as total_quantity'))->first();
        $last = Entry::latest('created_at')->first();

        return [
            'count'      => (int) ($entries->total_entries ?? 0),
            'quantity'   => (float) ($entries->total_quantity ?? 0),
            'last_date'  => $last?->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 📦 Datos para formularios
     */
    public function formData(): array
    {
        return [
            'products'   => Product::select('id', 'name')->get(),
            'suppliers'  => Supplier::select('id', 'name')->get(),
        ];
    }
}
