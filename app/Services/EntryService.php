<?php
/*
namespace App\Services;

use App\Models\Entry;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\Warehouse;
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


    // Listar todas las entradas con información detallada

    public function getAllEntries()
    {
        return Entry::with(['product.category', 'supplier', 'location.warehouse'])
            ->get()
            ->map(function ($entry) {
                $warehouseName = $entry->location && $entry->location->warehouse
                    ? $entry->location->warehouse->name
                    : 'Sin almacén';
                $locationName = $entry->location
                    ? "{$warehouseName} - {$entry->location->aisle}-{$entry->location->row}"
                    : 'Sin ubicación';

                return [
                    'id' => $entry->id,
                    'producto' => $entry->product->name ?? 'Desconocido',
                    'categoria' => $entry->product->category->name ?? 'Sin categoría',
                    'proveedor' => $entry->supplier->name ?? 'Desconocido',
                    'fecha' => $entry->created_at?->format('d/m/Y'),
                    'lote' => $entry->lot ?? '',
                    'cantidad' => $entry->quantity . ' ' . ($entry->unit ?? ''),
                    'location' => $locationName,
                    'min_stock' => $entry->min_stock,
                ];
            });
    }


    // Crear entrada con inventario y usuario

    public function createEntryWithInventoryAndUser(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;
            $data['stock'] = $data['quantity'] ?? 0;

            $entry = Entry::create($data);

            $inventory = Inventory::firstOrCreate(
                ['product_id' => $entry->product_id, 'lot' => $entry->lot],
                [
                    'stock' => $entry->quantity,
                    'min_stock' => $entry->min_stock ?? 0,
                    'location_id' => $entry->location_id,
                    'warehouse_id' => $entry->warehouse_id,
                    'user_id' => $userId,
                ]
            );

            if ($inventory->wasRecentlyCreated === false && isset($data['quantity'])) {
                $inventory->stock += $data['quantity'];
                $inventory->save();
            }

            if ($this->alertService) {
                $this->alertService->checkStock($inventory);
            }

            return $entry->load(['product.category', 'supplier', 'location.warehouse']);
        });
    }


   // Obtener entrada por ID

    public function getEntryById(int $id)
    {
        $entry = Entry::with(['product.category', 'supplier', 'location.warehouse'])->findOrFail($id);

        $warehouseName = $entry->location && $entry->location->warehouse
            ? $entry->location->warehouse->name
            : 'Sin almacén';
        $locationName = $entry->location
            ? "{$warehouseName} - {$entry->location->aisle}-{$entry->location->row}"
            : 'Sin ubicación';

        return [
            'id' => $entry->id,
            'producto' => $entry->product->name ?? 'Desconocido',
            'categoria' => $entry->product->category->name ?? 'Sin categoría',
            'proveedor' => $entry->supplier->name ?? 'Desconocido',
            'fecha' => $entry->created_at?->format('d/m/Y'),
            'lote' => $entry->lot ?? '',
            'cantidad' => $entry->quantity . ' ' . ($entry->unit ?? ''),
            'location' => $locationName,
            'min_stock' => $entry->min_stock,
        ];
    }


    // Actualizar entrada

    public function updateEntry(array $data, int $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $entry = Entry::findOrFail($id);
            $oldQuantity = $entry->quantity;
            $entry->update($data);

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

            return $entry->load(['product.category', 'supplier', 'location.warehouse']);
        });
    }


    // Eliminar entrada

    public function deleteEntry(int $id): void
    {
        DB::transaction(function () use ($id) {
            $entry = Entry::findOrFail($id);

            $inventory = Inventory::where('product_id', $entry->product_id)
                ->where('lot', $entry->lot)
                ->first();

            if ($inventory) {
                $inventory->stock -= $entry->quantity;
                $inventory->save();

                if ($this->alertService) {
                    $this->alertService->checkStock($inventory);
                }
            }

            $entry->delete();
        });
    }


    // Resumen de entradas

    public function getSummary(): array
    {
        $entries = Entry::selectRaw('COUNT(id) as total_entries, SUM(quantity) as total_quantity')->first();
        $last = Entry::latest('created_at')->first();

        return [
            'count' => (int) ($entries->total_entries ?? 0),
            'quantity' => (float) ($entries->total_quantity ?? 0),
            'last_date' => $last?->created_at?->format('Y-m-d H:i:s'),
        ];
    }


   //Datos para formularios

    public function formData(): array
    {
        $locations = Location::with('warehouse')->get()->map(function ($loc) {
            $warehouseName = $loc->warehouse ? $loc->warehouse->name : 'Sin almacén';
            $loc->display_name = "{$warehouseName} - {$loc->aisle}-{$loc->row}";
            return $loc;
        });

        return [
            'products' => Product::select('id','name')->get(),
            'suppliers' => Supplier::select('id','name')->get(),
            'locations' => $locations,
            'warehouses' => Warehouse::select('id','name')->get(),
        ];
    }
}
    */



namespace App\Services;

use App\Models\Entry;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\Warehouse;
use App\Services\AlertService;
use Illuminate\Support\Facades\DB;

class EntryService
{
    protected ?AlertService $alertService;

    public function __construct(AlertService $alertService = null)
    {
        $this->alertService = $alertService;
    }

    public function getAllEntries()
    {
        return Entry::with(['product.category', 'supplier', 'location.warehouse', 'warehouse'])
            ->get()
            ->map(fn($entry) => [
                'id' => $entry->id,
                'producto' => $entry->product->name ?? 'Desconocido',
                'categoria' => $entry->product->category->name ?? 'Sin categoría',
                'proveedor' => $entry->supplier->name ?? 'Desconocido',
                'fecha' => $entry->created_at?->format('d/m/Y'),
                'lote' => $entry->lot ?? '',
                'cantidad' => $entry->quantity . ' ' . ($entry->unit ?? ''),
                'location' => $entry->location
                    ? ($entry->location->warehouse->name ?? 'Sin almacén')
                      . ' - ' . $entry->location->aisle . '-' . $entry->location->row
                    : 'Sin ubicación',
                'warehouse' => $entry->warehouse->name ?? 'Sin almacén',
                'min_stock' => $entry->min_stock,
            ]);
    }

    public function createEntryWithInventoryAndUser(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;
            $data['stock'] = $data['quantity'] ?? 0;

            $entry = Entry::create($data);

            $inventory = Inventory::firstOrCreate(
                ['product_id' => $entry->product_id, 'lot' => $entry->lot],
                [
                    'stock' => $entry->quantity,
                    'min_stock' => $entry->min_stock ?? 0,
                    'location_id' => $entry->location_id,
                    'warehouse_id' => $entry->warehouse_id,
                    'user_id' => $userId,
                ]
            );

            if (!$inventory->wasRecentlyCreated && isset($data['quantity'])) {
                $inventory->stock += $data['quantity'];
                $inventory->save();
            }

            if ($this->alertService) {
                $this->alertService->checkStock($inventory);
            }

            return $entry->load(['product', 'supplier', 'location.warehouse', 'warehouse']);
        });
    }

    public function getEntryById(int $id)
    {
        $entry = Entry::with(['product.category', 'supplier', 'location.warehouse', 'warehouse'])->findOrFail($id);
        return [
            'id' => $entry->id,
            'producto' => $entry->product->name ?? 'Desconocido',
            'categoria' => $entry->product->category->name ?? 'Sin categoría',
            'proveedor' => $entry->supplier->name ?? 'Desconocido',
            'fecha' => $entry->created_at?->format('d/m/Y'),
            'lote' => $entry->lot ?? '',
            'cantidad' => $entry->quantity . ' ' . ($entry->unit ?? ''),
            'location' => $entry->location
                ? ($entry->location->warehouse->name ?? 'Sin almacén')
                  . ' - ' . $entry->location->aisle . '-' . $entry->location->row
                : 'Sin ubicación',
            'warehouse' => $entry->warehouse->name ?? 'Sin almacén',
            'min_stock' => $entry->min_stock,
        ];
    }

    public function updateEntry(array $data, int $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $entry = Entry::findOrFail($id);
            $oldQuantity = $entry->quantity;
            $entry->update($data);

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

            return $entry->load(['product', 'supplier', 'location.warehouse', 'warehouse']);
        });
    }

    public function deleteEntry(int $id): void
    {
        DB::transaction(function () use ($id) {
            $entry = Entry::findOrFail($id);

            $inventory = Inventory::where('product_id', $entry->product_id)
                ->where('lot', $entry->lot)
                ->first();

            if ($inventory) {
                $inventory->stock -= $entry->quantity;
                $inventory->save();

                if ($this->alertService) {
                    $this->alertService->checkStock($inventory);
                }
            }

            $entry->delete();
        });
    }

    public function getSummary(): array
    {
        $entries = Entry::selectRaw('COUNT(id) as total_entries, SUM(quantity) as total_quantity')->first();
        $last = Entry::latest('created_at')->first();

        return [
            'count' => (int) ($entries->total_entries ?? 0),
            'quantity' => (float) ($entries->total_quantity ?? 0),
            'last_date' => $last?->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    // Datos optimizados para formularios
    public function formData(): array
    {
        $locations = Location::with('warehouse')->get()->map(function ($loc) {
            $warehouseName = $loc->warehouse ? $loc->warehouse->name : 'Sin almacén';
            return [
                'id' => $loc->id,
                'display_name' => "{$warehouseName} - {$loc->aisle}-{$loc->row}",
            ];
        });

        $products = Product::select('id','name')->get()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
        ]);

        $suppliers = Supplier::select('id','name')->get()->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
        ]);

        $warehouses = Warehouse::select('id','name')->get()->map(fn($w) => [
            'id' => $w->id,
            'name' => $w->name,
        ]);

        return compact('products', 'suppliers', 'locations', 'warehouses');
    }
}

