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
            $message = "El stock del producto '{$inventory->product->name}' está en 0. ¡Reabastecimiento urgente!";
        } elseif ($inventory->stock < $inventory->min_stock) {
            $type = 'low_stock';
            $message = "El stock del producto '{$inventory->product->name}' está por debajo del mínimo permitido.";
        }

        // ✅ Si el stock está normal → resolver alertas activas
        if (!$type) {
            Alert::where('inventory_id', $inventory->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                ]);

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
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

        // 🆕 Crear nueva alerta
        $alert = Alert::create([
            'inventory_id' => $inventory->id,
            'product_id'   => $inventory->product_id,
            'alert_type'   => $type,
            'status'       => 'active',
            'message'      => $message,
            'date'         => now(),
        ]);

        Log::warning("🚨 Nueva alerta '{$type}' creada para inventario ID {$inventory->id}");

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
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        Log::info("✅ Alerta ID {$id} resuelta manualmente.");

        return $alert;
    }

    /**
     * 🆕 Crear alerta manual y enviar notificación
     */
    public function createManualAlert(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'alert_type'   => 'required|string',
            'message'      => 'nullable|string',
        ]);

        $inventory = Inventory::findOrFail($validated['inventory_id']);

        $alert = Alert::create([
            'date'         => now(),
            'alert_type'   => $validated['alert_type'],
            'product_id'   => $inventory->product_id,
            'inventory_id' => $inventory->id,
            'status'       => 'active',
            'message'      => $validated['message'] ?? 'Alerta generada manualmente.',
        ]);

        $this->sendNotification($alert);

        return $alert;
    }

    /**
     * ✉️ Enviar notificación de alerta (correo o evento)
     */
    private function sendNotification(Alert $alert)
    {
        try {
            $emailDestino = 'ivanslee77@gmail.com'; // 📩 Personaliza este correo o usa usuario dinámico

            Notification::route('mail', $emailDestino)
                ->notify(new StockAlertNotification($alert));

            Log::info("📧 Notificación enviada a {$emailDestino} para producto ID {$alert->product_id}");
        } catch (\Exception $e) {
            Log::error("❌ Error al enviar notificación de alerta: {$e->getMessage()}");
        }
    }
}
