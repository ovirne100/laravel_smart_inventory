<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'image'
    ];

    protected $casts = [
        'expiration_date' => 'date:Y-m-d',
    ];

    /** 🔹 Accessor para URL completa de imagen */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return url($this->image);
        }
        return null;
    }

    /** 🔹 Relaciones */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Alias en español (mantén ambas si quieres compatibilidad)
    public function categoria()
    {
        return $this->category();
    }

    public function detalles()
    {
        return $this->hasMany(ProductDetail::class);
    }

    /** 🔹 Relación con inventario */
    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'product_id');
    }

    /** 🔹 Scopes - ¡RENOMBRADOS para evitar conflictos! */

    // ✅ Scope renombrado: scopeCategory -> scopeFilterByCategory
    public function scopeFilterByCategory($query, $category = null)
    {
        if ($category !== null) {
            return $query->where('category_id', $category);
        }
        return $query;
    }

    public function scopeStatus($query, $status = null)
    {
        if ($status !== null) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min !== null && $max !== null) {
            return $query->whereBetween('price', [$min, $max]);
        }
        return $query;
    }

    public function scopeSearch($query, $term = null)
    {
        if ($term) {
            return $query->where('name', 'like', "%$term%");
        }
        return $query;
    }

    // Relación con Supplier (muchos a muchos)
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier', 'product_id', 'supplier_id')
                    ->withPivot('unit_cost', 'supplier_reference')
                    ->withTimestamps();
    }
}
