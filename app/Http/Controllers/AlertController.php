<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlertController extends Controller
{
    /**
     * 📦 Obtener todas las alertas (con producto, inventario y proveedor)
     */
    public function index(Request $request)
    {
        try {
            $query = Alert::with([
                'product' => function($query) {
                    $query->with('suppliers'); // Cargar proveedores del producto
                },
                'inventory' => function($query) {
                    $query->with(['product' => function($q) {
                        $q->with('suppliers'); // Cargar proveedores desde inventario también
                    }]);
                }
            ]);

            // 🔹 Filtros opcionales desde Angular
            if ($request->filled('alert_type')) {
                $query->where('alert_type', $request->alert_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $alerts = $query->orderByDesc('created_at')->get();

            // 🔹 Transformar respuesta para asegurar que supplier esté disponible
            $alerts = $alerts->map(function($alert) {
                // Obtener proveedor disponible
                $supplier = null;
                $suppliersArray = [];

                // Obtener product_id para consultar suppliers
                $productId = $alert->product_id;
                if ($alert->product) {
                    $productId = $alert->product->id;
                } elseif ($alert->inventory && $alert->inventory->product) {
                    $productId = $alert->inventory->product->id;
                }
                
                // Intentar obtener suppliers directamente desde la base de datos
                if ($productId) {
                    try {
                        // Consulta directa a la tabla pivot y suppliers
                        $suppliersFromDB = DB::table('product_supplier')
                            ->join('suppliers', 'product_supplier.supplier_id', '=', 'suppliers.id')
                            ->where('product_supplier.product_id', $productId)
                            ->select('suppliers.id', 'suppliers.name', 'suppliers.email', 'suppliers.phone', 'suppliers.address', 'suppliers.tax_id')
                            ->get();
                        
                        if ($suppliersFromDB->isNotEmpty()) {
                            $supplier = $suppliersFromDB->first();
                            $suppliersArray = $suppliersFromDB->map(function($s) {
                                return [
                                    'id' => $s->id,
                                    'name' => $s->name,
                                    'email' => $s->email ?? null,
                                    'phone' => $s->phone ?? null,
                                    'address' => $s->address ?? null,
                                    'tax_id' => $s->tax_id ?? null,
                                ];
                            })->toArray();
                        } else {
                            // Si no hay suppliers en la tabla pivot, intentar con la relación Eloquent
                            if ($alert->product) {
                                if (!$alert->product->relationLoaded('suppliers')) {
                                    $alert->product->load('suppliers');
                                }
                                $suppliersCollection = $alert->product->suppliers;
                                if ($suppliersCollection && $suppliersCollection->isNotEmpty()) {
                                    $supplier = $suppliersCollection->first();
                                    $suppliersArray = $suppliersCollection->map(function($s) {
                                        return [
                                            'id' => $s->id,
                                            'name' => $s->name,
                                            'email' => $s->email ?? null,
                                            'phone' => $s->phone ?? null,
                                            'address' => $s->address ?? null,
                                            'tax_id' => $s->tax_id ?? null,
                                        ];
                                    })->toArray();
                                }
                            }
                            
                            // Si aún no hay supplier, intentar desde inventory.product
                            if (!$supplier && $alert->inventory && $alert->inventory->product) {
                                if (!$alert->inventory->product->relationLoaded('suppliers')) {
                                    $alert->inventory->product->load('suppliers');
                                }
                                $suppliersCollection = $alert->inventory->product->suppliers;
                                if ($suppliersCollection && $suppliersCollection->isNotEmpty()) {
                                    $supplier = $suppliersCollection->first();
                                    $suppliersArray = $suppliersCollection->map(function($s) {
                                        return [
                                            'id' => $s->id,
                                            'name' => $s->name,
                                            'email' => $s->email ?? null,
                                            'phone' => $s->phone ?? null,
                                            'address' => $s->address ?? null,
                                            'tax_id' => $s->tax_id ?? null,
                                        ];
                                    })->toArray();
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // En caso de error, intentar con la relación Eloquent
                        if ($alert->product) {
                            if (!$alert->product->relationLoaded('suppliers')) {
                                $alert->product->load('suppliers');
                }
                            $suppliersCollection = $alert->product->suppliers;
                            if ($suppliersCollection && $suppliersCollection->isNotEmpty()) {
                                $supplier = $suppliersCollection->first();
                                $suppliersArray = $suppliersCollection->map(function($s) {
                                    return [
                                        'id' => $s->id,
                                        'name' => $s->name,
                                        'email' => $s->email ?? null,
                                        'phone' => $s->phone ?? null,
                                        'address' => $s->address ?? null,
                                        'tax_id' => $s->tax_id ?? null,
                                    ];
                                })->toArray();
                            }
                        }
                    }
                }

                // Construir respuesta manualmente para garantizar estructura
                $alertData = [
                    'id' => $alert->id,
                    'product_id' => $alert->product_id,
                    'inventory_id' => $alert->inventory_id,
                    'alert_type' => $alert->alert_type,
                    'status' => $alert->status,
                    'message' => $alert->message,
                    'date' => $alert->date?->toDateTimeString() ?? $alert->date,
                    'resolved_at' => $alert->resolved_at?->toDateTimeString() ?? $alert->resolved_at,
                    'created_at' => $alert->created_at?->toDateTimeString() ?? $alert->created_at,
                    'updated_at' => $alert->updated_at?->toDateTimeString() ?? $alert->updated_at,
                ];

                // Agregar product con suppliers
                if ($alert->product) {
                    $alertData['product'] = [
                        'id' => $alert->product->id,
                        'name' => $alert->product->name,
                        'reference' => $alert->product->reference,
                        'category_id' => $alert->product->category_id,
                        'suppliers' => $suppliersArray,
                    ];
                    
                    // Agregar supplier como objeto único
                    if ($supplier) {
                        $alertData['product']['supplier'] = [
                            'id' => $supplier->id,
                            'name' => $supplier->name,
                            'email' => $supplier->email,
                            'phone' => $supplier->phone,
                            'address' => $supplier->address,
                            'tax_id' => $supplier->tax_id,
                        ];
                }
                }

                // Agregar inventory con product y suppliers
                if ($alert->inventory) {
                    $inventoryData = [
                        'id' => $alert->inventory->id ?? null,
                        'lot' => $alert->inventory->lot,
                        'lot_number' => $alert->inventory->lot_number ?? $alert->inventory->lot,
                        'stock' => $alert->inventory->stock,
                        'min_stock' => $alert->inventory->min_stock,
                        'product_id' => $alert->inventory->product_id,
                        'warehouse_id' => $alert->inventory->warehouse_id,
                        'ubicacion_interna' => $alert->inventory->ubicacion_interna,
                    ];

                    // Agregar product dentro de inventory
                    if ($alert->inventory->product) {
                        $inventoryData['product'] = [
                            'id' => $alert->inventory->product->id,
                            'name' => $alert->inventory->product->name,
                            'reference' => $alert->inventory->product->reference,
                            'category_id' => $alert->inventory->product->category_id,
                            'suppliers' => $suppliersArray,
                        ];
                        
                        // Agregar supplier como objeto único
                        if ($supplier) {
                            $inventoryData['product']['supplier'] = [
                                'id' => $supplier->id,
                                'name' => $supplier->name,
                                'email' => $supplier->email,
                                'phone' => $supplier->phone,
                                'address' => $supplier->address,
                                'tax_id' => $supplier->tax_id,
                            ];
                }
                    }

                    $alertData['inventory'] = $inventoryData;
                }

                return $alertData;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Alertas obtenidas correctamente.',
                'data' => $alerts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener alertas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📦 Mostrar una alerta específica
     */
    public function show($id)
    {
        try {
            $alert = Alert::with([
                'product' => function($query) {
                    $query->with('suppliers');
                },
                'inventory' => function($query) {
                    $query->with(['product' => function($q) {
                        $q->with('suppliers');
                    }]);
                }
            ])->findOrFail($id);

            // Obtener proveedor disponible
            $supplier = null;
            $suppliersArray = [];

            // Obtener product_id para consultar suppliers
            $productId = $alert->product_id;
            if ($alert->product) {
                $productId = $alert->product->id;
            } elseif ($alert->inventory && $alert->inventory->product) {
                $productId = $alert->inventory->product->id;
            }
            
            // Intentar obtener suppliers directamente desde la base de datos
            if ($productId) {
                try {
                    // Consulta directa a la tabla pivot y suppliers
                    $suppliersFromDB = DB::table('product_supplier')
                        ->join('suppliers', 'product_supplier.supplier_id', '=', 'suppliers.id')
                        ->where('product_supplier.product_id', $productId)
                        ->select('suppliers.id', 'suppliers.name', 'suppliers.email', 'suppliers.phone', 'suppliers.address', 'suppliers.tax_id')
                        ->get();
                    
                    if ($suppliersFromDB->isNotEmpty()) {
                        $supplier = $suppliersFromDB->first();
                        $suppliersArray = $suppliersFromDB->map(function($s) {
                            return [
                                'id' => $s->id,
                                'name' => $s->name,
                                'email' => $s->email ?? null,
                                'phone' => $s->phone ?? null,
                                'address' => $s->address ?? null,
                                'tax_id' => $s->tax_id ?? null,
                            ];
                        })->toArray();
                    } else {
                        // Si no hay suppliers en la tabla pivot, intentar con la relación Eloquent
                        if ($alert->product) {
                            if (!$alert->product->relationLoaded('suppliers')) {
                                $alert->product->load('suppliers');
                            }
                            $suppliersCollection = $alert->product->suppliers;
                            if ($suppliersCollection && $suppliersCollection->isNotEmpty()) {
                                $supplier = $suppliersCollection->first();
                                $suppliersArray = $suppliersCollection->map(function($s) {
                                    return [
                                        'id' => $s->id,
                                        'name' => $s->name,
                                        'email' => $s->email ?? null,
                                        'phone' => $s->phone ?? null,
                                        'address' => $s->address ?? null,
                                        'tax_id' => $s->tax_id ?? null,
                                    ];
                                })->toArray();
                            }
                        }
                        
                        // Si aún no hay supplier, intentar desde inventory.product
                        if (!$supplier && $alert->inventory && $alert->inventory->product) {
                            if (!$alert->inventory->product->relationLoaded('suppliers')) {
                                $alert->inventory->product->load('suppliers');
                            }
                            $suppliersCollection = $alert->inventory->product->suppliers;
                            if ($suppliersCollection && $suppliersCollection->isNotEmpty()) {
                                $supplier = $suppliersCollection->first();
                                $suppliersArray = $suppliersCollection->map(function($s) {
                                    return [
                                        'id' => $s->id,
                                        'name' => $s->name,
                                        'email' => $s->email ?? null,
                                        'phone' => $s->phone ?? null,
                                        'address' => $s->address ?? null,
                                        'tax_id' => $s->tax_id ?? null,
                                    ];
                                })->toArray();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // En caso de error, intentar con la relación Eloquent
                    if ($alert->product) {
                        if (!$alert->product->relationLoaded('suppliers')) {
                            $alert->product->load('suppliers');
                        }
                        $suppliersCollection = $alert->product->suppliers;
                        if ($suppliersCollection && $suppliersCollection->isNotEmpty()) {
                            $supplier = $suppliersCollection->first();
                            $suppliersArray = $suppliersCollection->map(function($s) {
                                return [
                                    'id' => $s->id,
                                    'name' => $s->name,
                                    'email' => $s->email ?? null,
                                    'phone' => $s->phone ?? null,
                                    'address' => $s->address ?? null,
                                    'tax_id' => $s->tax_id ?? null,
                                ];
                            })->toArray();
                        }
                    }
                }
            }

            // Construir respuesta manualmente para garantizar estructura
            $alertData = [
                'id' => $alert->id,
                'product_id' => $alert->product_id,
                'inventory_id' => $alert->inventory_id,
                'alert_type' => $alert->alert_type,
                'status' => $alert->status,
                'message' => $alert->message,
                'date' => $alert->date?->toDateTimeString() ?? $alert->date,
                'resolved_at' => $alert->resolved_at?->toDateTimeString() ?? $alert->resolved_at,
                'created_at' => $alert->created_at?->toDateTimeString() ?? $alert->created_at,
                'updated_at' => $alert->updated_at?->toDateTimeString() ?? $alert->updated_at,
            ];

            // Agregar product con suppliers
            if ($alert->product) {
                $alertData['product'] = [
                    'id' => $alert->product->id,
                    'name' => $alert->product->name,
                    'reference' => $alert->product->reference,
                    'category_id' => $alert->product->category_id,
                    'suppliers' => $suppliersArray,
                ];
                
                // Agregar supplier como objeto único
                if ($supplier) {
                    $alertData['product']['supplier'] = [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                        'email' => $supplier->email,
                        'phone' => $supplier->phone,
                        'address' => $supplier->address,
                        'tax_id' => $supplier->tax_id,
                    ];
                }
            }

            // Agregar inventory con product y suppliers
            if ($alert->inventory) {
                $inventoryData = [
                    'id' => $alert->inventory->id ?? null,
                    'lot' => $alert->inventory->lot,
                    'lot_number' => $alert->inventory->lot_number ?? $alert->inventory->lot,
                    'stock' => $alert->inventory->stock,
                    'min_stock' => $alert->inventory->min_stock,
                    'product_id' => $alert->inventory->product_id,
                    'warehouse_id' => $alert->inventory->warehouse_id,
                    'ubicacion_interna' => $alert->inventory->ubicacion_interna,
                ];

                // Agregar product dentro de inventory
                if ($alert->inventory->product) {
                    $inventoryData['product'] = [
                        'id' => $alert->inventory->product->id,
                        'name' => $alert->inventory->product->name,
                        'reference' => $alert->inventory->product->reference,
                        'category_id' => $alert->inventory->product->category_id,
                        'suppliers' => $suppliersArray,
                    ];
                    
                    // Agregar supplier como objeto único
                    if ($supplier) {
                        $inventoryData['product']['supplier'] = [
                            'id' => $supplier->id,
                            'name' => $supplier->name,
                            'email' => $supplier->email,
                            'phone' => $supplier->phone,
                            'address' => $supplier->address,
                            'tax_id' => $supplier->tax_id,
                        ];
                    }
                }

                $alertData['inventory'] = $inventoryData;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Alerta encontrada correctamente.',
                'data' => $alertData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener la alerta.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * ✅ Resolver una alerta manualmente
     */
    public function resolve($id)
    {
        try {
            $alert = Alert::findOrFail($id);

            $alert->update([
                'status' => Alert::STATUS_RESOLVED,
                'resolved_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Alerta resuelta correctamente.',
                'data' => $alert
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al resolver la alerta.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 Reabrir una alerta resuelta
     */
    public function reopen($id)
    {
        try {
            $alert = Alert::findOrFail($id);

            $alert->update([
                'status' => Alert::STATUS_ACTIVE,
                'resolved_at' => null
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Alerta reabierta correctamente.',
                'data' => $alert
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al reabrir la alerta.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🧹 Eliminar una alerta
     */
    public function destroy($id)
    {
        try {
            $alert = Alert::findOrFail($id);
            $alert->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Alerta eliminada correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la alerta.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Obtener estadísticas de alertas
     */
    public function stats()
    {
        try {
            $stats = [
                'total' => Alert::count(),
                'active' => Alert::active()->count(),
                'resolved' => Alert::resolved()->count(),
                'low_stock' => Alert::lowStock()->count(),
                'out_of_stock' => Alert::outOfStock()->count(),
                'today' => Alert::whereDate('date', today())->count(),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Estadísticas obtenidas correctamente.',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener estadísticas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
