<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

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
                // Obtener el primer proveedor disponible
                $supplier = null;

                if ($alert->product && $alert->product->suppliers->isNotEmpty()) {
                    $supplier = $alert->product->suppliers->first();
                } elseif ($alert->inventory && $alert->inventory->product && $alert->inventory->product->suppliers->isNotEmpty()) {
                    $supplier = $alert->inventory->product->suppliers->first();
                }

                // Añadir supplier directamente al producto para facilitar acceso en frontend
                if ($alert->product && $supplier) {
                    $alert->product->supplier = $supplier;
                }

                if ($alert->inventory && $alert->inventory->product && $supplier) {
                    $alert->inventory->product->supplier = $supplier;
                }

                return $alert;
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

            // Añadir supplier como objeto único
            $supplier = null;
            if ($alert->product && $alert->product->suppliers->isNotEmpty()) {
                $supplier = $alert->product->suppliers->first();
                $alert->product->supplier = $supplier;
            } elseif ($alert->inventory && $alert->inventory->product && $alert->inventory->product->suppliers->isNotEmpty()) {
                $supplier = $alert->inventory->product->suppliers->first();
                $alert->inventory->product->supplier = $supplier;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Alerta encontrada correctamente.',
                'data' => $alert
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
