<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Inventory;
use App\Notifications\StockAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * 🚨 Verifica el stock y crea, actualiza o resuelve alertas según sea necesario.
     */
    public function checkStock(Inventory $inventory)
    {
        $type = null;
        $message = null;

        // 🔎 Determinar tipo de alerta según el stock actual
        if ($inventory->stock <= 0) {
            $type = 'critical';
            $message = 'El stock del producto "' . $inventory->product->name . '" está en 0. ¡Reabastecimiento urgente!';
        } elseif ($inventory->stock < $inventory->min_stock) {
            $type = 'low_stock';
            $message = 'El stock del producto "' . $inventory->product->name . '" está por debajo del mínimo permitido.';
        }

        // ✅ Si el stock está normal → resolver todas las alertas activas
        if (!$type) {
            Alert::where('inventory_id', $inventory->id)
                ->where('status', 'active')
                ->update(['status' => 'resolved']);
            Log::info("🟢 Alerta resuelta automáticamente para inventario ID {$inventory->id}");
            return null;
        }

        // 🔍 Verificar si ya existe una alerta activa del mismo tipo
        $existingAlert = Alert::where('inventory_id', $inventory->id)
            ->where('alert_type', $type)
            ->where('status', 'active')
            ->first();

        if ($existingAlert) {
            return $existingAlert;
        }

        // 🔒 Resolver otras alertas activas antes de crear una nueva
        Alert::where('inventory_id', $inventory->id)
            ->where('status', 'active')
            ->update(['status' => 'resolved']);

        // 🆕 Crear nueva alerta
        $alert = Alert::create([
            'inventory_id' => $inventory->id,
            'product_id'   => $inventory->product_id,
            'alert_type'   => $type,
            'status'       => 'active',
            'message'      => $message,
            'date'         => now(),
        ]);

        Log::warning("🚨 Nueva alerta {$type} creada para inventario ID {$inventory->id}");

        $this->sendNotification($alert);

        return $alert;
    }

    /**
     * 📋 Listar todas las alertas
     */
    public function getAllAlerts()
    {
        return Alert::with(['inventory.product'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * ⚠️ Listar solo las alertas activas
     */
    public function getActiveAlerts()
    {
        return Alert::with(['inventory.product'])
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * ✅ Resolver alerta manualmente
     */
    public function resolveAlert($id)
    {
        $alert = Alert::findOrFail($id);
        $alert->update(['status' => 'resolved']);
        Log::info("✅ Alerta ID {$id} resuelta manualmente.");

        return $alert;
    }

    /**
     * 🆕 Crear alerta manual y enviar notificación
     */
    public function createManualAlert(Request $request)
    {
        $alert = Alert::create([
            'date'         => now(),
            'alert_type'   => $request->alert_type,
            'product_id'   => $request->product_id,
            'inventory_id' => $request->inventory_id,
            'status'       => 'active',
            'message'      => $request->message ?? 'Alerta generada manualmente.',
        ]);

        $this->sendNotification($alert);

        return $alert;
    }

    /**
     * ✉️ Enviar notificación de alerta
     */
    private function sendNotification(Alert $alert)
    {
        try {
            $emailDestino = 'ivanslee77@gmail.com'; // 📩 Cambia aquí tu correo

            Notification::route('mail', $emailDestino)
                ->notify(new StockAlertNotification($alert));

            Log::info("📧 Notificación enviada correctamente a {$emailDestino} para producto ID {$alert->product_id}");
        } catch (\Exception $e) {
            Log::error('❌ Error al enviar notificación de alerta: ' . $e->getMessage());
        }
    }
}
