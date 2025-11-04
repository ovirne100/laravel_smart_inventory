<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    /* ----------------- ATRIBUTOS ----------------- */

    protected $fillable = [
        'product_id',
        'inventory_id',
        'alert_type',
        'status',
        'message',
        'date',
        'resolved_at',
    ];

    protected $casts = [
        'date' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'type_label'];

    /* ----------------- CONSTANTES ----------------- */

    // Tipos de alerta
    const TYPE_LOW_STOCK = 'bajo_stock';
    const TYPE_OUT_OF_STOCK = 'sin_stock';

    // Estados
    const STATUS_PENDING = 'pendiente';      // ⬅️ AGREGADO para compatibilidad
    const STATUS_ACTIVE = 'pendiente';       // ⬅️ Mantenido por compatibilidad
    const STATUS_RESOLVED = 'resuelta';
    const STATUS_IN_PROCESS = 'en_proceso';  // ⬅️ AGREGADO para órdenes

    /* ----------------- RELACIONES ----------------- */

    /**
     * 📦 Relación con producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 📦 Relación con inventario
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * 📦 Relación con órdenes (una alerta puede generar múltiples órdenes)
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /* ----------------- SCOPES ----------------- */

    /**
     * Scope para alertas activas/pendientes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para alertas pendientes (alias de active)
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para alertas resueltas
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope para alertas en proceso
     */
    public function scopeInProcess($query)
    {
        return $query->where('status', self::STATUS_IN_PROCESS);
    }

    /**
     * Scope para alertas por bajo stock
     */
    public function scopeLowStock($query)
    {
        return $query->where('alert_type', self::TYPE_LOW_STOCK);
    }

    /**
     * Scope para alertas por falta total de stock
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('alert_type', self::TYPE_OUT_OF_STOCK);
    }

    /* ----------------- HELPERS ----------------- */

    /**
     * Verifica si la alerta está activa/pendiente
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verifica si la alerta está pendiente (alias de isActive)
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica si la alerta está resuelta
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Verifica si la alerta está en proceso
     */
    public function isInProcess(): bool
    {
        return $this->status === self::STATUS_IN_PROCESS;
    }

    /**
     * 📦 Verificar si tiene órdenes asociadas
     */
    public function hasOrders(): bool
    {
        return $this->orders()->exists();
    }

    /**
     * 🔄 Marcar alerta como en proceso
     */
    public function markAsInProcess(): bool
    {
        return $this->update([
            'status' => self::STATUS_IN_PROCESS,
        ]);
    }

    /**
     * ✅ Marcar alerta como resuelta
     */
    public function markAsResolved(?string $additionalMessage = null): bool
    {
        $message = $this->message;

        if ($additionalMessage) {
            $message .= ' ' . $additionalMessage;
        }

        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'message' => $message,
            'resolved_at' => now(),
        ]);
    }

    /* ----------------- ACCESSORS ----------------- */

    /**
     * Retorna una etiqueta legible del estado
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE, self::STATUS_PENDING => 'Pendiente',
            self::STATUS_RESOLVED => 'Resuelta',
            self::STATUS_IN_PROCESS => 'En Proceso',
            default => 'Desconocido',
        };
    }

    /**
     * Retorna una etiqueta legible del tipo de alerta
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->alert_type) {
            self::TYPE_LOW_STOCK => 'Stock Bajo',
            self::TYPE_OUT_OF_STOCK => 'Sin Stock',
            default => 'Desconocido',
        };
    }
}
