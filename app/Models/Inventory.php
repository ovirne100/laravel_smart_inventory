<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable =['location_id', 'current_stock', 'update_date', 'quantity'];
    public function location() { return $this->belongsTo(Location::class); }
    public function productos() { return $this->hasMany(Product::class); }
}
