<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'address',
        'email',
        'phone'
    ];

    // Listas blancas para includes, filtros y orden
    protected array $allowIncluded = []; // Puedes agregar relaciones si las necesitas
    protected array $allowFilter   = ['id', 'name', 'email', 'phone', 'address'];
    protected array $allowSort     = ['id', 'name'];

    /* ================== SCOPES ================== */

    public function scopeIncluded(Builder $query)
    {
<<<<<<< HEAD
        if (empty($this->allowIncluded) || empty(request('included'))) return;

        $relations = explode(',', request('included'));
        $relations = array_filter($relations, fn($rel) => in_array($rel, $this->allowIncluded));

        if (!empty($relations)) {
            $query->with($relations);
        }
=======
        return $this->belongsToMany(Product::class, 'product_supplier', 'supplier_id', 'product_id')
                    ->withPivot('unit_cost', 'supplier_reference');
>>>>>>> 0ed22cfdc47b44ea2a0de0d18550105196679823
    }

    public function scopeFilter(Builder $query)
    {
        if (empty(request('filter'))) return;

        foreach (request('filter') as $filter => $value) {
            if (in_array($filter, $this->allowFilter)) {
                if (is_numeric($value)) {
                    $query->where($filter, $value);
                } elseif (strtotime($value) !== false) {
                    $query->whereDate($filter, $value);
                } else {
                    $query->where($filter, 'LIKE', "%$value%");
                }
            }
        }
    }

    public function scopeSort(Builder $query)
    {
        if (empty(request('sort'))) return;

        $sortFields = explode(',', request('sort'));
        foreach ($sortFields as $sortField) {
            $direction = 'asc';
            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }
            if (in_array($sortField, $this->allowSort)) {
                $query->orderBy($sortField, $direction);
            }
        }
    }

    public function scopeGetOrPaginate(Builder $query)
    {
        $perPage = intval(request('perPage', 0));
        return $perPage > 0 ? $query->paginate($perPage) : $query->get();
    }
}
