<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\AlertController;

class Inventory extends Model
{
    protected $fillable = [
        'stock',
        'min_stock',
        'product_id',
        'user_id',
        'warehouse_id',
        'location_id', // Asegúrate de incluirlo si usas ubicaciones internas
    ];

    // 🔹 Permitir relaciones, filtros y ordenamiento
    protected $allowIncluded = ['product', 'user', 'warehouse', 'alerts'];
    protected $allowFilter   = ['id', 'product_id', 'warehouse_id', 'location_id'];
    protected $allowSort     = ['id', 'product_id', 'stock'];

    // ==================================================
    // 🧩 RELACIONES
    // ==================================================
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // 🔥 Relación con alertas (una relación de uno a muchos)
    public function alerts()
    {
        return $this->hasMany(Alert::class, 'inventory_id');
    }

    // ==================================================
    // 🔎 SCOPES (Filtros, Orden y Relaciones)
    // ==================================================
    public function scopeIncluded(Builder $query)
    {
        $relations = explode(',', request('included', ''));
        $relations = array_intersect($relations, $this->allowIncluded);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    public function scopeFilter(Builder $query)
    {
        if (empty($this->allowFilter)) {
            return $query;
        }

        $filters = request('filter', []) + request()->only($this->allowFilter);

        foreach ($filters as $field => $value) {
            if (in_array($field, $this->allowFilter)) {
                if (is_numeric($value)) {
                    $query->where($field, $value);
                } elseif (strtotime($value)) {
                    $query->whereDate($field, $value);
                } else {
                    $query->where($field, 'LIKE', '%' . $value . '%');
                }
            }
        }

        return $query;
    }

    public function scopeSort(Builder $query)
    {
        $sortFields = explode(',', request('sort', ''));
        foreach ($sortFields as $field) {
            $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
            $field = ltrim($field, '-');

            if (in_array($field, $this->allowSort)) {
                $query->orderBy($field, $direction);
            }
        }

        return $query;
    }

    public function scopeGetOrPaginate(Builder $query)
    {
        $perPage = intval(request('perPage', 0));
        return $perPage > 0 ? $query->paginate($perPage) : $query->get();
    }

    // ==================================================
    // 🧠 EVENTOS AUTOMÁTICOS DEL MODELO
    // ==================================================
    // Esta sección hará que se verifique el stock automáticamente
    // cada vez que se crea o actualiza un inventario.
    protected static function booted()
    {
        static::created(function ($inventory) {
            AlertController::checkStock($inventory);
        });

        static::updated(function ($inventory) {
            AlertController::checkStock($inventory);
        });
    }
}
