<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Notifications\StockAlertNotification;
use App\Mail\StockAlertMail;
use App\Mail\AlertResolvedMail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    /**
     * 🔍 Verifica el stock de un inventario y crea/actualiza alertas según corresponda
     */
    public function checkStock(Inventory $inventory): void
    {
        $product = $inventory->product;

        if (!$product) {
            Log::warning("⚠️ Inventario {$inventory->id} sin producto asociado.");
            return;
        }

        $currentStock = $inventory->stock;
        $minStock = (int)($inventory->min_stock ?? 0);
        $alertType = $this->determineAlertType($currentStock, $minStock);

        DB::transaction(function () use ($product, $inventory, $currentStock, $alertType) {
            $this->processAlert($product, $inventory, $currentStock, $alertType);
        });
    }

    /**
     * 🧮 Determina el tipo de alerta según el stock actual
     */
    private function determineAlertType(int $currentStock, int $minStock): ?string
    {
        if ($currentStock == 0) {
            return Alert::TYPE_OUT_OF_STOCK;
        }

        if ($currentStock > 0 && $currentStock < $minStock) {
            return Alert::TYPE_LOW_STOCK;
        }

        return null;
    }

    /**
     * ⚙️ Procesa la creación, actualización o resolución de alertas
     */
    private function processAlert(Product $product, Inventory $inventory, int $currentStock, ?string $alertType): void
    {
        $activeAlert = Alert::where('product_id', $product->id)
            ->where('status', Alert::STATUS_ACTIVE)
            ->first();

        if ($alertType) {
            $message = $this->generateMessage($product, $currentStock, $alertType);

            $alert = Alert::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'status' => Alert::STATUS_ACTIVE
                ],
                [
                    'inventory_id' => $inventory->id,
                    'alert_type' => $alertType,
                    'message' => $message,
                    'date' => now(),
                    'resolved_at' => null,
                ]
            );

            Log::info("📢 Alerta creada o actualizada para producto {$product->name}");

            $this->notifyUsers($alert);
        } elseif ($activeAlert) {
            $this->autoResolveAlert($activeAlert, $product, $currentStock);
        }
    }

    /**
     * 📩 Envía notificaciones a administradores y empleados (plataforma + correo)
     */
    private function notifyUsers(Alert $alert): void
    {
        try {
            $users = User::whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'Admin', 'empleado', 'Empleado']);
            })->get();

            if ($users->isEmpty()) {
                Log::warning("⚠️ No se encontraron usuarios para notificar la alerta {$alert->id}");
                return;
            }

            foreach ($users as $user) {
                try {
                    $user->notify(new StockAlertNotification($alert));

                    if ($user->email) {
                        Mail::to($user->email)->send(new StockAlertMail($alert));
                        usleep(100000);
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Error al notificar usuario {$user->email}: " . $e->getMessage());
                }
            }

            Log::info("✅ Notificaciones enviadas correctamente para la alerta {$alert->id}.");

        } catch (\Exception $e) {
            Log::error("❌ Error general al enviar notificaciones de alerta {$alert->id}: " . $e->getMessage());
        }
    }

    /**
     * 📩 Notificación cuando una alerta se resuelve (manual o automática)
     */
    private function notifyAlertResolved(Alert $alert): void
    {
        try {
            $users = User::whereHas('role', function ($q) {
                $q->whereIn('name', ['admin', 'Admin', 'empleado', 'Empleado']);
            })->get();

            foreach ($users as $user) {
                if ($user->email) {
                    Mail::to($user->email)->send(new AlertResolvedMail($alert));
                    usleep(100000);
                }
            }

            Log::info("✅ Correos de resolución enviados correctamente para alerta {$alert->id}");

        } catch (\Exception $e) {
            Log::error("❌ Error al notificar resolución de alerta {$alert->id}: " . $e->getMessage());
        }
    }

    /**
     * ✅ Resuelve automáticamente una alerta cuando el stock se normaliza
     */
    private function autoResolveAlert(Alert $alert, Product $product, int $currentStock): void
    {
        $alert->update([
            'status' => Alert::STATUS_RESOLVED,
            'message' => "El stock del producto '{$product->name}' se ha normalizado ({$currentStock} unidades).",
            'resolved_at' => now(),
        ]);

        $this->notifyAlertResolved($alert);
    }

    /**
     * 📝 Genera el mensaje de alerta
     */
    private function generateMessage(Product $product, int $stock, string $alertType): string
    {
        return match ($alertType) {
            Alert::TYPE_OUT_OF_STOCK => "El producto '{$product->name}' está sin stock (0 unidades).",
            Alert::TYPE_LOW_STOCK => "El producto '{$product->name}' tiene stock bajo ({$stock} unidades disponibles).",
            default => "Alerta desconocida para el producto '{$product->name}'."
        };
    }

    /**
     * ✅ Resolver manualmente una alerta
     */
    public function resolveAlert(int $id): Alert
    {
        $alert = Alert::findOrFail($id);

        if ($alert->status === Alert::STATUS_RESOLVED) {
            return $alert;
        }

        $alert->update([
            'status' => Alert::STATUS_RESOLVED,
            'message' => $alert->message . ' (Resuelta manualmente)',
            'resolved_at' => now(),
        ]);

        $this->notifyAlertResolved($alert);

        return $alert->fresh(['product.suppliers', 'inventory']);
    }

    /**
     * 📋 Obtiene alertas con filtros opcionales
     */
    public function getAlerts(array $filters = []): Collection
    {
        $query = Alert::with(['product.suppliers', 'inventory']);

        if (!empty($filters['alert_type'])) {
            $query->where('alert_type', $filters['alert_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 🔄 Verifica el stock de todo el inventario
     */
    public function checkAllInventory(): void
    {
        $inventories = Inventory::with('product')->get();

        foreach ($inventories as $inventory) {
            try {
                $this->checkStock($inventory);
            } catch (\Exception $e) {
                Log::error("❌ Error al verificar inventario {$inventory->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * 📊 Obtiene estadísticas de alertas
     */
    public function getStats(): array
    {
        return [
            'total'        => Alert::count(),
            'active'       => Alert::active()->count(),
            'resolved'     => Alert::resolved()->count(),
            'low_stock'    => Alert::lowStock()->count(),
            'out_of_stock' => Alert::outOfStock()->count(),
            'today'        => Alert::whereDate('date', today())->count(),
        ];
    }
}
