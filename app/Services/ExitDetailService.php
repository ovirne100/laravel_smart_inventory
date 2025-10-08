<?php

namespace App\Services;

use App\Models\ExitDetail;

class ExitDetailService
{
    public function getAll()
    {
        return ExitDetail::with(['producto', 'salida'])->get();
    }

    public function getById($id)
    {
        return ExitDetail::with(['producto', 'salida'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return ExitDetail::create($data);
    }

    public function update($id, array $data)
    {
        $detail = ExitDetail::findOrFail($id);
        $detail->update($data);
        return $detail;
    }

    public function delete($id)
    {
        $detail = ExitDetail::findOrFail($id);
        return $detail->delete();
    }
}
