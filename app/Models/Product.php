<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'category_id', 'reference', 'unit_measurement', 'batch', 'expiration_date',
        'image'
    ];

    protected $casts = [
        'expiration_date' => 'date:Y-m-d',
    ];

    // Accessor para la URL completa de la imagen
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return url($this->image); // ejemplo: http://localhost/uploads/products/xxxx.jpg
        }
        return null;
    }

    public function categoria() { return $this->belongsTo(Category::class, 'category_id'); }
    public function detalles() { return $this->hasMany(ProductDetail::class); }

       // Scope para filtrar por categoría
    public function scopeCategory($query, $categoryId)
    {
        if ($categoryId) {
            return $query->where('category_id', $categoryId);
        }
        return $query;
    }


    // Scope para filtrar por estado (activo/inactivo)
    public function scopeStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }




    // Scope para filtrar por rango de precio
    public function scopePriceRange($query, $min, $max)
    {
        if ($min !== null && $max !== null) {
            return $query->whereBetween('price', [$min, $max]);
        }
        return $query;
    }

     // Scope para búsqueda por nombre parcial
    public function scopeSearch($query, $term)
    {
        if ($term) {
            return $query->where('name', 'like', "%$term%");
        }
        return $query;
    }

}
