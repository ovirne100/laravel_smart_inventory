<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product_supplier extends Model
{
    protected $table = 'product_supplier';

    protected $fillable = [
        'supplier_id',
        'product_id',
        'unit_cost',
        'supplier_reference'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
