<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
     protected $fillable = ['role_id', 'dep_buy_id', 'date', 'status'];
    public function role() { return $this->belongsTo(Role::class); }
    public function depBuy() { return $this->belongsTo(DepBuy::class); }
    public function detalles() { return $this->hasMany(ProductDetail::class); }
}
