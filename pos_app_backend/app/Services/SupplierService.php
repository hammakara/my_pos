<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierService extends BaseService
{
    public function getAllSuppliers($perPage = 15)
    {
        return Supplier::paginate($perPage);
    }

    public function createSupplier(array $data)
    {
        return Supplier::create($data);
    }

    public function getSupplierById(int $id)
    {
        return Supplier::findOrFail($id);
    }

    public function updateSupplier(int $id, array $data)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($data);
        return $supplier;
    }

    public function deleteSupplier(int $id)
    {
        return Supplier::destroy($id);
    }
}
