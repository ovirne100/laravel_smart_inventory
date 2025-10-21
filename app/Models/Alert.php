<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'inventory_id',
        'alert_type',
        'status',
        'message',
        'date',
        'resolved_at'
    ];

    protected $casts = [
        'date' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // ✅ CAMBIADO: "resuelto" → "resuelta" (femenino, natural en español)
    const TYPE_LOW_STOCK = 'bajo_stock';
    const TYPE_OUT_OF_STOCK = 'sin_stock';
    const STATUS_ACTIVE = 'pendiente';
    const STATUS_RESOLVED = 'resuelta';  // ✅ AHORA ES "resuelta"

    // Relaciones
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeLowStock($query)
    {
        return $query->where('alert_type', self::TYPE_LOW_STOCK);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('alert_type', self::TYPE_OUT_OF_STOCK);
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    // Accessors para labels
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Pendiente',
            self::STATUS_RESOLVED => 'Resuelta',  // ✅ También cambiar aquí
            default => 'Desconocido'
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->alert_type) {
            self::TYPE_LOW_STOCK => 'Stock Bajo',
            self::TYPE_OUT_OF_STOCK => 'Sin Stock',
            default => 'Desconocido'
        };
    }
}
