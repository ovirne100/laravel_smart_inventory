<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\Alert;
use App\Services\AlertService;

class Inventory extends Model
{
    // ==================================================
    // 🧱 CAMPOS ASIGNABLES
    // ==================================================
    protected $fillable = [
        'product_id',
        'user_id',
        'warehouse_id',
        'ubicacion_interna',
        'lot',
        'stock',
        'min_stock',
    ];

    // ==================================================
    // ⚙️ CONFIGURACIÓN DE LISTAS BLANCAS PARA FILTROS
    // ==================================================
    protected array $allowIncluded = ['product', 'user', 'warehouse', 'alerts'];
    protected array $allowFilter   = ['product_id', 'warehouse_id', 'ubicacion_interna', 'lot', 'stock'];
    protected array $allowSort     = ['id', 'product_id', 'stock', 'min_stock', 'created_at'];

    // ==================================================
    // 🔗 RELACIONES
    // ==================================================
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class, 'inventory_id');
    }

    // ==================================================
    // 🔎 SCOPES (INCLUSIÓN, FILTRO, ORDEN, PAGINACIÓN)
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
                    $query->where($field, 'LIKE', "%{$value}%");
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
    // 🧠 LÓGICA DE NEGOCIO
    // ==================================================
    public function isLowStock(): bool
    {
        return $this->stock < $this->min_stock;
    }

    // Alias para compatibilidad (usa 'stock' internamente)
    public function getQuantityAttribute()
    {
        return $this->stock;
    }

    // ==================================================
    // 🚨 EVENTOS AUTOMÁTICOS
    // ==================================================
    protected static function booted()
    {
        // 🔐 Asigna automáticamente el usuario logueado
        static::creating(function ($inventory) {
            if (Auth::check()) {
                $inventory->user_id = Auth::id();
            }
        });

        // 🚨 Verificar alertas después de crear o actualizar usando AlertService
        static::created(function ($inventory) {
            app(AlertService::class)->checkStock($inventory);
        });

        static::updated(function ($inventory) {
            app(AlertService::class)->checkStock($inventory);
        });
    }
}
