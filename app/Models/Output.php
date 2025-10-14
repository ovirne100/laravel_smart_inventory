<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Output extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden asignar masivamente
     */
    protected $fillable = [
        'product_id',
        'quantity',
        'unit',
        'lot',
        'user_id',
        'inventory_id',
    ];

    /**
     * Cast de tipos de datos
     */
    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* =======================
     |   RELACIONES
     ======================= */

    /**
     * Una salida pertenece a un producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Una salida pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Una salida pertenece a un inventario
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    /* =======================
     |   ACCESSORS / GETTERS
     ======================= */

    public function getCantidadFormateadaAttribute()
    {
        return "{$this->quantity} " . ($this->unit ?? '');
    }

    public function getNombreProductoAttribute()
    {
        return $this->product->name ?? 'Producto desconocido';
    }

    public function getNombreUsuarioAttribute()
    {
        return $this->user->name ?? 'Desconocido';
    }

    public function getNombreCategoriaAttribute()
    {
        return $this->product->category->name ?? 'Sin categoría';
    }

    /**
     * Formato automático de fechas en JSON
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /* =======================
     |   SCOPES PERSONALIZADOS
     ======================= */

    /**
     * Filtrar por producto
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Filtrar por usuario
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filtrar por inventario
     */
    public function scopeByInventory($query, $inventoryId)
    {
        return $query->where('inventory_id', $inventoryId);
    }

    /**
     * Filtrar por rango de fechas
     */
    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Ordenar de la más reciente a la más antigua
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Filtrar salidas con stock bajo (por debajo de un umbral)
     */
    public function scopeLowStock($query, $threshold = 5)
    {
        return $query->whereHas('inventory', function ($q) use ($threshold) {
            $q->where('stock', '<', $threshold);
        });
    }

    /**
     * Incluir relaciones comunes por defecto
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['product.category', 'user', 'inventory']);
    }
}
