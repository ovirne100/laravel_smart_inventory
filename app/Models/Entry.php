<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Entry extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'unit',
        'lot',
        'supplier_id',
        'warehouse_id',
        'location_id',
        'min_stock',
        'stock',
        'user_id',
    ];

    protected array $allowIncluded = ['product', 'supplier', 'location', 'warehouse'];
    protected array $allowFilter   = ['id', 'product_id', 'supplier_id', 'warehouse_id', 'location_id', 'quantity'];
    protected array $allowSort     = ['id', 'quantity', 'product_id', 'created_at'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes para filtros, inclusión de relaciones y paginación
    public function scopeIncluded(Builder $query): void
    {
        $included = request('included');
        if (!$included) return;

        $relations = array_filter(explode(',', $included), fn($r) => in_array($r, $this->allowIncluded));
        $query->with($relations);
    }

    public function scopeFilter(Builder $query): void
    {
        $filters = request('filter', []);
        foreach ($filters as $key => $value) {
            if (in_array($key, $this->allowFilter)) {
                $query->where($key, $value);
            }
        }
    }

    public function scopeSort(Builder $query): void
    {
        $sort = request('sort');
        if (!$sort) return;

        foreach (explode(',', $sort) as $field) {
            $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
            $field = ltrim($field, '-');
            if (in_array($field, $this->allowSort)) {
                $query->orderBy($field, $direction);
            }
        }
    }

    public function scopeGetOrPaginate(Builder $query)
    {
        $perPage = request('perPage');
        return $perPage ? $query->paginate(intval($perPage)) : $query->get();
    }
}
