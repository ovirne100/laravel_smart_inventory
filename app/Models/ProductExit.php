<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductExit extends Model
{
    use HasFactory;

    protected $table = 'product_exits';
    protected $primaryKey = 'idSalida';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'fecha_salida',
        'tipo_salida',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'id');
    }

    public function detalles()
    {
        return $this->hasMany(ExitDetail::class, 'idSalida', 'idSalida');
    }
}
