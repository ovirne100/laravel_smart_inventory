<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $primaryKey = 'supplier_id';
    protected $fillable = ['name', 'email', 'phone', 'address', 'tax_id', 'status'];

    // Many-to-Many Relationship with Product
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_supplier', 'supplier_id', 'product_id')
                    ->withPivot('unit_cost', 'supplier_reference');
    }

    // Relationship to Orders (Pedido) as per your diagram
    public function orders()
    {
        return $this->hasMany(Order::class, 'supplier_id');
    }
}
