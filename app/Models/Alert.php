<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Alert extends Model
{
    // ==================================================
    // 🧱 CAMPOS PERMITIDOS
    // ==================================================
    protected $fillable = [
        'inventory_id',   // Relación con el inventario
        'product_id',     // Producto asociado
        'date',           // Fecha de la alerta
        'alert_type',     // Tipo de alerta (por ejemplo: 'bajo_stock')
        'message',        // Mensaje descriptivo
        'status',         // Estado ('activa', 'resuelta', etc.)
        'resolved_at',    // Fecha de resolución
    ];

    // ==================================================
    // ⚙️ CONFIGURACIÓN DE FILTROS, RELACIONES Y ORDEN
    // ==================================================
    protected array $allowIncluded = ['inventory.product', 'inventory.user', 'inventory.location'];
    protected array $allowFilter   = ['id', 'date', 'alert_type', 'status', 'product_id', 'inventory_id', 'resolved_at'];
    protected array $allowSort     = ['id', 'date', 'alert_type', 'status', 'resolved_at'];

    // ==================================================
    // 🔗 RELACIONES
    // ==================================================
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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
                } elseif ($this->isDate($value)) {
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
    // 🧠 MÉTODOS AUXILIARES
    // ==================================================
    protected function isDate($value): bool
    {
        return strtotime($value) !== false;
    }
}
