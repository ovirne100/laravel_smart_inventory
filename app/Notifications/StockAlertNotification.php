<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
//use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StockAlertNotification extends Notification //implements ShouldQueue
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
        $inventory = $this->alert->inventory ?? null;
        $productName = $inventory && $inventory->product ? $inventory->product->name : 'Producto desconocido';
        $stock = $inventory ? ($inventory->stock ?? 'N/A') : 'N/A';
        $minStock = $inventory ? ($inventory->min_stock ?? 'N/A') : 'N/A';
        $message = $this->alert->message ?? 'Se ha generado una alerta de stock.';

        return (new MailMessage)
            ->subject("🚨 Alerta de Stock: {$productName}")
            ->greeting("Hola " . ($notifiable->name ?? 'Administrador') . ",")
            ->line("Se ha detectado un problema con el inventario del producto **{$productName}**.")
            ->line("📦 **Stock actual:** {$stock}")
            ->line("🔻 **Stock mínimo permitido:** {$minStock}")
            ->line("⚠️ **Mensaje:** {$message}")
            ->action('Ver alerta en el sistema', url("/dashboard/alertas"))
            ->line('Por favor, revise el inventario para evitar quiebres de stock.')
            ->salutation('Atentamente, Smart Inventory');
    }

    /**
     * 📥 Representación de la notificación para base de datos
     */
    public function toArray($notifiable)
    {
        $inventory = $this->alert->inventory ?? null;
        $product = $inventory && $inventory->product ? $inventory->product->name : 'Producto desconocido';

        return [
            'alert_id'    => $this->alert->id,
            'product'     => $product,
            'message'     => $this->alert->message,
            'stock'       => $inventory ? $inventory->stock : null,
            'min_stock'   => $inventory ? $inventory->min_stock : null,
            'alert_type'  => $this->alert->alert_type,
            'status'      => $this->alert->status,
            'date'        => $this->alert->created_at,
        ];
    }
}
