<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExitDetail extends Model
{
    use HasFactory;

    protected $table = 'exit_details';
    protected $primaryKey = 'idDetalle_salida';
    public $timestamps = false;

    protected $fillable = [
        'idProducto',
        'idSalida',
        'cantidad',
        'destino',
    ];

    public function salida()
    {
        return $this->belongsTo(ProductExit::class, 'idSalida', 'idSalida');
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'idProducto', 'idProducto');
    }
}
