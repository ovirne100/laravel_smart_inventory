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

    /* ----------------- CONSTANTES ----------------- */

    // Tipos de alerta
    const TYPE_LOW_STOCK = 'bajo_stock';
    const TYPE_OUT_OF_STOCK = 'sin_stock';

    // Estados
    const STATUS_ACTIVE = 'pendiente';
    const STATUS_RESOLVED = 'resuelta';

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
     * Scope para alertas activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para alertas resueltas
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
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
     * Verifica si la alerta está activa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verifica si la alerta está resuelta
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * 📦 Verificar si tiene órdenes asociadas
     */
    public function hasOrders(): bool
    {
        return $this->orders()->exists();
    }

    /* ----------------- ACCESSORS ----------------- */

    /**
     * Retorna una etiqueta legible del estado
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Pendiente',
            self::STATUS_RESOLVED => 'Resuelta',
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
