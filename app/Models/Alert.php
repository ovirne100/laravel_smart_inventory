<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = ['inventory_id', 'date', 'alert_type', 'status'];
    public function inventory() { return $this->belongsTo(Inventory::class); }
}
