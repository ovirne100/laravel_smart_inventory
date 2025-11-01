<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\ProductDetail;
use App\Models\Inventory;
use App\Models\Supplier;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'reference',
        'unit_measurement',
        'batch',
        'expiration_date',
        'image',
    ];

    protected $casts = [
        'expiration_date' => 'date:Y-m-d',
    ];

    /* ============================================================
     *  ACCESSORS
     * ============================================================ */
    /**
     * 🔹 Retorna la URL completa de la imagen
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? url($this->image) : null;
    }

    /* ============================================================
     *  RELACIONES
     * ============================================================ */

    /**
     * 🔹 Relación con la categoría del producto
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

/**
 * 🔹 Relación con órdenes
 */
public function orders()
{
    return $this->hasMany(Order::class);
}

    /**
     * Alias en español (opcional)
     */
    public function categoria()
    {
        return $this->category();
    }

    /**
     * 🔹 Relación con los detalles del producto
     */
    public function detalles()
    {
        return $this->hasMany(ProductDetail::class);
    }

    /**
     * 🔹 Relación uno a uno con inventario
     */
    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'product_id');
    }

    /**
     * 🔹 Relación muchos a muchos con proveedores
     *
     * ⚠️ Solo se mantienen los IDs — no se incluyen columnas pivot que no existen.
     */
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier', 'product_id', 'supplier_id');
    }

    /* ============================================================
     *  SCOPES (Filtros personalizados)
     * ============================================================ */

    /**
     * 🔹 Filtrar por categoría
     */
    public function scopeFilterByCategory($query, $category = null)
    {
        if ($category !== null) {
            return $query->where('category_id', $category);
        }
        return $query;
    }

    /**
     * 🔹 Filtrar por estado
     */
    public function scopeStatus($query, $status = null)
    {
        if ($status !== null) {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * 🔹 Filtrar por rango de precios (para futuras ampliaciones)
     */
    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min !== null && $max !== null) {
            return $query->whereBetween('price', [$min, $max]);
        }
        return $query;
    }

    /**
     * 🔹 Buscar por nombre o referencia
     */
    public function scopeSearch($query, $term = null)
    {
        if ($term) {
            return $query->where('name', 'like', "%$term%")
                         ->orWhere('reference', 'like', "%$term%");
        }
        return $query;
    }
}
