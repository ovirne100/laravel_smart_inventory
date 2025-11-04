<?php
/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_id', // <-- agrega esto
    ];


    // Listas blancas para includes, filtros y orden
    protected array $allowIncluded = ['products']; // puedes agregar relaciones aquí
    protected array $allowFilter   = ['id', 'name', 'email', 'phone', 'address'];
    protected array $allowSort     = ['id', 'name'];

    // ================== RELACIONES ================== //
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_supplier', 'supplier_id', 'product_id')
                    ->withPivot('unit_cost', 'supplier_reference');
    }

   // ================== SCOPES ================== //
    public function scopeIncluded(Builder $query)
    {
        if (empty($this->allowIncluded) || empty(request('included'))) return;

        $relations = explode(',', request('included'));
        $relations = array_filter($relations, fn($rel) => in_array($rel, $this->allowIncluded));

        if (!empty($relations)) {
            $query->with($relations);
        }
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
*/


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Product;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_id',
    ];

    // Listas blancas para includes, filtros y orden
    protected array $allowIncluded = ['products'];
    protected array $allowFilter   = ['id', 'name', 'email', 'phone', 'address'];
    protected array $allowSort     = ['id', 'name'];

    // ================== RELACIONES ================== //
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_supplier', 'supplier_id', 'product_id')
                    ->withPivot('unit_cost', 'supplier_reference');
                  //  ->withTimestamps();
    }

    /**
 * 🔹 Relación con órdenes
 */
public function orders()
{
    return $this->hasMany(Order::class);
}

    // ================== SCOPES ================== //
    public function scopeIncluded(Builder $query)
    {
        if (empty($this->allowIncluded) || empty(request('included'))) {
            return;
        }

        $relations = explode(',', request('included'));
        $relations = array_filter($relations, fn($rel) => in_array($rel, $this->allowIncluded));

        if (!empty($relations)) {
            $query->with($relations);
        }
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
