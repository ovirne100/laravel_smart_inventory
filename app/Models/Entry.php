<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
     protected $fillable = ['role_id', 'income_type'];
    public function role() { return $this->belongsTo(Role::class); }
    public function notas() { return $this->hasMany(EntryNote::class); }
}
