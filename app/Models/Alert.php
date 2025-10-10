<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Alert extends Model
{
    protected $fillable = [
        'date',
        'alert_type',
        'product_id',
        'status',
        'inventory_id',
        'message',
        'resolved_at',
    ];

    // Permitir relaciones, filtros y ordenamientos desde las querys
    protected array $allowIncluded = ['inventory.product', 'inventory.user'];
    protected array $allowFilter   = ['id', 'date', 'alert_type', 'status', 'product_id', 'inventory_id', 'resolved_at'];
    protected array $allowSort     = ['id', 'date', 'alert_type', 'status', 'resolved_at'];

    // ================== RELACIONES ==================
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // ================== SCOPES ==================

    /**
     * 🔗 Carga relaciones dinámicamente (usando ?included=inventory.product)
     */
    public function scopeIncluded(Builder $query)
    {
        $relations = explode(',', request('included', ''));
        $relations = array_intersect($relations, $this->allowIncluded);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    /**
     * 🔍 Filtra resultados (?filter[field]=value o ?field=value)
     */
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

    /**
     * ↕️ Ordena resultados (?sort=-date,alert_type)
     */
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

    /**
     * 📄 Retorna resultados paginados o completos (?perPage=10)
     */
    public function scopeGetOrPaginate(Builder $query)
    {
        $perPage = intval(request('perPage', 0));
        return $perPage > 0 ? $query->paginate($perPage) : $query->get();
    }

    // ================== HELPERS ==================
    protected function isDate($value): bool
    {
        return strtotime($value) !== false;
    }
}
