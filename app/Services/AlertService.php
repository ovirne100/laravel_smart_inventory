<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Inventory;
use App\Models\User;
use App\Notifications\StockAlertNotification;
use App\Mail\StockAlertMail;
use App\Mail\AlertResolvedMail; // 👈 NUEVO
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    /**
     * Verifica el stock de un inventario y crea/actualiza alertas según corresponda
     */
    public function checkStock(Inventory $inventory): void
    {
        $product = $inventory->product;

        if (!$product) {
            Log::warning("Inventario {$inventory->id} sin producto asociado");
            return;
        }

        $currentStock = $inventory->stock;
        $minStock = (int) ($inventory->min_stock ?? 0);

        $alertType = $this->determineAlertType($currentStock, $minStock);

        DB::transaction(function () use ($product, $inventory, $currentStock, $alertType) {
            $this->processAlert($product, $inventory, $currentStock, $alertType);
        });
    }

    /**
     * Determina el tipo de alerta según el stock actual
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
     * Procesa la creación, actualización o resolución de alertas
     */
    private function processAlert($product, Inventory $inventory, int $currentStock, ?string $alertType): void
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

            // 📩 Enviar notificación a administradores y empleados
            $this->notifyUsers($alert);
        } elseif ($activeAlert) {
            $this->autoResolveAlert($activeAlert, $product, $currentStock);
        }
    }

    /**
     * 📢 Envía la notificación a empleados y administradores (por notificación + correo individual)
     */
    private function notifyUsers(Alert $alert): void
    {
        try {
            $users = User::whereHas('role', function ($query) {
                $query->whereIn('name', ['admin', 'Admin', 'empleado', 'Empleado']);
            })->get();

            Log::info("🔍 Total de usuarios encontrados para alerta {$alert->id}: " . $users->count());

            if ($users->isEmpty()) {
                Log::warning("⚠️ No se encontraron usuarios para enviar la alerta {$alert->id}");
                return;
            }

            $emailsSent = 0;
            $notificationsSent = 0;

            // Enviar a cada usuario individualmente
            foreach ($users as $user) {
                try {
                    Log::info("📧 Procesando usuario: {$user->name} ({$user->email})");

                    // Notificación en la plataforma
                    $user->notify(new StockAlertNotification($alert));
                    $notificationsSent++;

                    // Correo individual
                    if ($user->email) {
                        Mail::to($user->email)->send(new StockAlertMail($alert));
                        $emailsSent++;
                        Log::info("✅ Correo enviado a: {$user->email}");

                        // Pequeño delay para evitar rate limiting
                        usleep(100000); // 0.1 segundos
                    } else {
                        Log::warning("⚠️ Usuario {$user->name} sin email");
                    }

                } catch (\Exception $e) {
                    Log::error("❌ Error al enviar a {$user->email}: {$e->getMessage()}");
                }
            }

            Log::info("✅ Resumen alerta {$alert->id}: {$notificationsSent} notificaciones, {$emailsSent} correos enviados.");

        } catch (\Exception $e) {
            Log::error("❌ Error general al enviar notificación de alerta {$alert->id}: {$e->getMessage()}");
        }
    }

    /**
     * 📢 NUEVO: Envía notificación cuando se resuelve una alerta
     */
    private function notifyAlertResolved(Alert $alert): void
    {
        try {
            $users = User::whereHas('role', function ($query) {
                $query->whereIn('name', ['admin', 'Admin', 'empleado', 'Empleado']);
            })->get();

            Log::info("🔍 Notificando resolución de alerta {$alert->id} a " . $users->count() . " usuarios");

            if ($users->isEmpty()) {
                Log::warning("⚠️ No se encontraron usuarios para notificar resolución de alerta {$alert->id}");
                return;
            }

            $emailsSent = 0;

            foreach ($users as $user) {
                try {
                    Log::info("✅ Notificando resolución a: {$user->name} ({$user->email})");

                    if ($user->email) {
                        Mail::to($user->email)->send(new AlertResolvedMail($alert));
                        $emailsSent++;
                        Log::info("✅ Correo de resolución enviado a: {$user->email}");

                        // Pequeño delay para evitar rate limiting
                        usleep(100000); // 0.1 segundos
                    }

                } catch (\Exception $e) {
                    Log::error("❌ Error al enviar resolución a {$user->email}: {$e->getMessage()}");
                }
            }

            Log::info("✅ Resolución de alerta {$alert->id}: {$emailsSent} correos enviados.");

        } catch (\Exception $e) {
            Log::error("❌ Error al notificar resolución de alerta {$alert->id}: {$e->getMessage()}");
        }
    }

    /**
     * Resuelve automáticamente una alerta cuando el stock se normaliza
     */
    private function autoResolveAlert(Alert $alert, $product, int $currentStock): void
    {
        $alert->update([
            'status' => Alert::STATUS_RESOLVED,
            'message' => "El stock del producto '{$product->name}' se ha normalizado ({$currentStock} unidades).",
            'resolved_at' => now(),
        ]);

        // 📩 NUEVO: Enviar notificación de resolución
        $this->notifyAlertResolved($alert);
    }

    /**
     * Genera el mensaje de alerta apropiado
     */
    private function generateMessage($product, int $stock, string $alertType): string
    {
        if ($alertType === Alert::TYPE_OUT_OF_STOCK) {
            return "El producto '{$product->name}' está sin stock (0 unidades).";
        }

        return "El producto '{$product->name}' tiene stock bajo ({$stock} unidades disponibles).";
    }

    /**
     * Resuelve manualmente una alerta
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

        // 📩 NUEVO: Enviar notificación de resolución manual
        $this->notifyAlertResolved($alert->fresh(['product', 'inventory']));

        return $alert->fresh(['product', 'inventory']);
    }

    /**
     * Obtiene alertas con filtros opcionales
     */
    public function getAlerts(array $filters = []): Collection
    {
        $query = Alert::with(['product', 'inventory']);

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
     * Verifica el stock de todo el inventario
     */
    public function checkAllInventory(): void
    {
        $inventories = Inventory::with('product')->get();

        foreach ($inventories as $inventory) {
            try {
                $this->checkStock($inventory);
            } catch (\Exception $e) {
                Log::error("Error al verificar inventario {$inventory->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Obtiene estadísticas de alertas
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
