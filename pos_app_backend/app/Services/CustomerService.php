<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService extends BaseService
{
    public function getAllCustomers($perPage = 15)
    {
        return Customer::paginate($perPage);
    }

    public function createCustomer(array $data)
    {
        return Customer::create($data);
    }

    public function getCustomerById(int $id)
    {
        return Customer::findOrFail($id);
    }

    public function updateCustomer(int $id, array $data)
    {
        $customer = Customer::findOrFail($id);
        $customer->update($data);
        return $customer;
    }

    public function deleteCustomer(int $id)
    {
        return Customer::destroy($id);
    }
}
