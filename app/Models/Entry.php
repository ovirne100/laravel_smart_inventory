<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Entry extends Model
{
    // Campos rellenables
    protected $fillable = [
        'product_id',
        'quantity',
        'unit',       // agregado
        'lot',        // agregado
        'supplier_id',
        'user_id',
        'inventory_id',
        'Warehouse_id'
    ];

    protected array $allowIncluded = ['product', 'supplier', 'user', 'inventory'];
    protected array $allowFilter   = ['id', 'product_id', 'supplier_id', 'user_id', 'inventory_id'];
    protected array $allowSort     = ['id', 'quantity', 'product_id', 'inventory_id'];

    /*
     |---------------------------------------------------------------------------
     | Relaciones
     |---------------------------------------------------------------------------
    */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /*
     |---------------------------------------------------------------------------
     | Scopes
     |---------------------------------------------------------------------------
    */
    public function scopeIncluded(Builder $query)
    {
        if (empty(request('included'))) return;

        $relations = explode(',', request('included'));
        $allowIncluded = collect($this->allowIncluded);

        $relations = array_filter($relations, fn($relation) => $allowIncluded->contains($relation));

        $query->with($relations);
    }

    public function scopeFilter(Builder $query)
    {
        if (empty(request('filter'))) return;

        $filters = request('filter');
        $allowFilter = collect($this->allowFilter);

        foreach ($filters as $filter => $value) {
            if ($allowFilter->contains($filter)) {
                if (is_numeric($value)) {
                    $query->where($filter, $value);
                } elseif (strtotime($value)) {
                    $query->whereDate($filter, $value);
                } else {
                    $query->where($filter, 'LIKE', '%' . $value . '%');
                }
            }
        }
    }

    public function scopeSort(Builder $query)
    {
        if (empty(request('sort'))) return;

        $sortFields = explode(',', request('sort'));
        $allowSort  = collect($this->allowSort);

        foreach ($sortFields as $sortField) {
            $direction = 'asc';

            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            if ($allowSort->contains($sortField)) {
                $query->orderBy($sortField, $direction);
            }
        }
    }

    public function scopeGetOrPaginate(Builder $query)
    {
        if (request('perPage')) {
            $perPage = intval(request('perPage'));
            if ($perPage > 0) {
                return $query->paginate($perPage);
            }
        }
        return $query->get();
    }
}
