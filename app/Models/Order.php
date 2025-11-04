<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_id',
        'product_id',
        'supplier_id',
        'user_id',
        'quantity',
        'status',
        'notes',
        'sent_at',
        'received_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status_label'];

    // Constantes de estado
    const STATUS_PENDING = 'pendiente';
    const STATUS_SENT = 'enviado';
    const STATUS_RECEIVED = 'recibido';
    const STATUS_CANCELLED = 'cancelado';

    /* ----------------- RELACIONES ----------------- */

    /**
     * Relación con la alerta que generó la orden
     */
    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }

    /**
     * Relación con el producto solicitado
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con el proveedor al que se le hace el pedido
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relación con el usuario que creó la orden
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ----------------- SCOPES ----------------- */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /* ----------------- HELPERS ----------------- */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Marcar orden como enviada
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Marcar orden como recibida
     */
    public function markAsReceived(): bool
    {
        return $this->update([
            'status' => self::STATUS_RECEIVED,
            'received_at' => now(),
        ]);
    }

    /**
     * Cancelar orden
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /* ----------------- ACCESSORS ----------------- */

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_SENT => 'Enviado',
            self::STATUS_RECEIVED => 'Recibido',
            self::STATUS_CANCELLED => 'Cancelado',
            default => 'Desconocido'
        };
    }

    /* ----------------- EVENTOS ----------------- */

    protected static function booted()
    {
        // Asignar automáticamente el usuario logueado al crear
        static::creating(function ($order) {
            if (Auth::check() && !$order->user_id) {
                $order->user_id = Auth::id();
            }

            // Establecer estado por defecto si no está definido
            if (!$order->status) {
                $order->status = self::STATUS_PENDING;
            }
        });
    }
}
