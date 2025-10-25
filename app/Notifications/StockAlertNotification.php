<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StockAlertNotification extends Notification
{
    use Queueable;

    public Alert $alert;

    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Canales por los que se enviará la notificación
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * ✉️ Contenido del correo
     */
    public function toMail($notifiable)
    {
        $inventory = $this->alert->inventory;
        $productName = $inventory?->product?->name ?? 'Producto desconocido';
        $stock = $inventory->stock ?? 'N/A';
        $minStock = $inventory->min_stock ?? 'N/A';
        $message = $this->alert->message ?? 'Se ha generado una alerta de stock.';
        $alertTypeLabel = $this->getAlertTypeLabel($this->alert->alert_type);

        return (new MailMessage)
            ->subject("🚨 {$alertTypeLabel}: {$productName}")
            ->greeting("Hola " . ($notifiable->name ?? 'Usuario') . ",")
            ->line("Se ha detectado un problema con el inventario del producto **{$productName}**.")
            ->line("📦 **Stock actual:** {$stock}")
            ->line("🔻 **Stock mínimo permitido:** {$minStock}")
            ->line("⚠️ **Tipo de alerta:** {$alertTypeLabel}")
            ->line("💬 **Mensaje:** {$message}")
            ->action('Ver alerta en el sistema', url("/dashboard/alertas"))
            ->line('Por favor, revise el inventario para evitar quiebres de stock.')
            ->salutation('Atentamente, Smart Inventory');
    }

    /**
     * 📥 Representación de la notificación para base de datos
     */
    public function toArray($notifiable)
    {
        $inventory = $this->alert->inventory;
        $productName = $inventory?->product?->name ?? 'Producto desconocido';
        $alertTypeLabel = $this->getAlertTypeLabel($this->alert->alert_type);

        return [
            'alert_id'   => $this->alert->id,
            'product'    => $productName,
            'message'    => $this->alert->message,
            'stock'      => $inventory->stock ?? null,
            'min_stock'  => $inventory->min_stock ?? null,
            'alert_type' => $this->alert->alert_type,
            'level'      => $alertTypeLabel,
            'status'     => $this->alert->status,
            'created_at' => $this->alert->created_at,
        ];
    }

    /**
     * 🔹 Convierte alert_type en un texto legible
     */
    private function getAlertTypeLabel(?string $type): string
    {
        return match($type) {
            'bajo_stock' => 'Stock Bajo',
            'sin_stock'  => 'Sin Stock',
            default      => 'Stock Desconocido',
        };
    }
}
