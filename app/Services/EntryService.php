<?php

namespace App\Services;

use App\Models\Entry;

class EntryService
{
    public function getAll($filters = [])
    {
        return Entry::query()
            ->product($filters['product_id'] ?? null)
            ->usuario($filters['user_id'] ?? null)
            ->porFecha($filters['desde'] ?? null, $filters['hasta'] ?? null)
            ->paginate(10);
    }

    public function create(array $data): Entry
    {
        return Entry::create($data);
    }
}
