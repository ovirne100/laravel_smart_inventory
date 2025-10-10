<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Alert;

class Inventory extends Model
{
    // ==================================================
    // 🧱 CAMPOS PERMITIDOS
    // ==================================================
    protected $fillable = [
        'product_id',
        'user_id',
        'location_id',  // ubicación interna o almacén lógico
        'stock',        // stock actual
        'min_stock',    // stock mínimo permitido
    ];

    // ==================================================
    // ⚙️ CONFIGURACIÓN DE FILTROS, RELACIONES Y ORDEN
    // ==================================================
    protected $allowIncluded = ['product', 'user', 'location', 'alerts'];
    protected $allowFilter   = ['id', 'product_id', 'location_id', 'stock'];
    protected $allowSort     = ['id', 'product_id', 'stock', 'min_stock'];

    // ==================================================
    // 🔗 RELACIONES
    // ==================================================
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

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
    // 🧠 MÉTODOS PERSONALIZADOS
    // ==================================================
    public function isLowStock()
    {
        return $this->stock < $this->min_stock;
    }

    // ==================================================
    // 🚨 EVENTOS AUTOMÁTICOS
    // ==================================================
    protected static function booted()
    {
        static::created(function ($inventory) {
            $inventory->checkAndCreateAlert();
        });

        static::updated(function ($inventory) {
            $inventory->checkAndCreateAlert();
        });
    }

    // ==================================================
    // ⚡ LÓGICA PARA CREAR ALERTAS
    // ==================================================
    public function checkAndCreateAlert()
    {
        // Si el stock está por debajo del mínimo
        if ($this->isLowStock()) {
            // Verificar si ya existe una alerta activa
            $alertExists = $this->alerts()
                ->where('status', 'pendiente')
                ->where('alert_type', 'bajo_stock')
                ->exists();

            if (!$alertExists) {
                Alert::create([
                    'inventory_id' => $this->id,
                    'product_id'   => $this->product_id,
                    'alert_type'   => 'bajo_stock',
                    'status'       => 'pendiente',
                    'message'      => "El producto '{$this->product->name}' tiene un stock bajo ({$this->stock}).",
                    'date'         => now(),
                ]);
            }
        }
    }
}
