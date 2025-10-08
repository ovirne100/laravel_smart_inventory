<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
       protected $fillable = ['warehouse_id', 'aisle', 'row', 'capacity'];
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function inventories() { return $this->hasMany(Inventory::class); }
}
