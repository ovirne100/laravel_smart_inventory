<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public function categoria()
    {
        return $this->belongsTo(Category::class, 'category_id');
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

    /** 🔹 Scopes */
    public function scopeCategory($query, $categoryId)
    {
        if ($categoryId) {
            return $query->where('category_id', $categoryId);
        }
        return $query;
    }

    public function scopeStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopePriceRange($query, $min, $max)
    {
        if ($min !== null && $max !== null) {
            return $query->whereBetween('price', [$min, $max]);
        }
        return $query;
    }

    public function scopeSearch($query, $term)
    {
        if ($term) {
            return $query->where('name', 'like', "%$term%");
        }
        return $query;
    }
}
