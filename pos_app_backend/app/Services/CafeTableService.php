<?php

namespace App\Services;

use App\Models\CafeTable;

class CafeTableService extends BaseService
{
    public function getAllTables()
    {
        return CafeTable::all();
    }

    public function createTable(array $data)
    {
        return CafeTable::create($data);
    }

    public function getTableById(int $id)
    {
        return CafeTable::findOrFail($id);
    }

    public function updateTable(int $id, array $data)
    {
        $table = CafeTable::findOrFail($id);
        $table->update($data);
        return $table;
    }

    public function updateStatus(int $id, string $status)
    {
        $table = CafeTable::findOrFail($id);
        $table->update(['status' => $status]);
        return $table;
    }

    public function deleteTable(int $id)
    {
        return CafeTable::destroy($id);
    }
}
