<?php


namespace App\Services;

use App\Models\ProductExit;

class ProductExitService
{
    public function getAll()
    {
        return ProductExit::with(['usuario', 'detalles'])->get();
    }

    public function getById($id)
    {
        return ProductExit::with(['usuario', 'detalles'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return ProductExit::create($data);
    }

    public function update($id, array $data)
    {
        $exit = ProductExit::findOrFail($id);
        $exit->update($data);
        return $exit;
    }

    public function delete($id)
    {
        $exit = ProductExit::findOrFail($id);
        return $exit->delete();
    }
}
