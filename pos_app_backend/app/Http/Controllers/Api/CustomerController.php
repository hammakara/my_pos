<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request)
    {
        $customers = Customer::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->customer_type, fn($q) => $q->where('customer_type', $request->customer_type))
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'nullable|email|unique:customers,email',
            'phone'         => 'nullable|string|max:50',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:20',
            'country'       => 'nullable|string|max:100',
            'tax_id'        => 'nullable|string|max:100',
            'customer_type' => 'nullable|in:individual,business',
            'notes'         => 'nullable|string',
        ]);

        $data['customer_number'] = 'CUST-' . strtoupper(uniqid());
        $data['status'] = 'active';

        $customer = $this->customerService->createCustomer($data);
        return response()->json(['data' => $customer], 201);
    }

    public function show(int $id)
    {
        $customer = Customer::withCount('orders')->findOrFail($id);
        return response()->json(['data' => $customer]);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'email'         => "nullable|email|unique:customers,email,{$id}",
            'phone'         => 'nullable|string|max:50',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:20',
            'country'       => 'nullable|string|max:100',
            'tax_id'        => 'nullable|string|max:100',
            'customer_type' => 'nullable|in:individual,business',
            'status'        => 'nullable|in:active,inactive',
            'notes'         => 'nullable|string',
        ]);

        $customer = $this->customerService->updateCustomer($id, $data);
        return response()->json(['data' => $customer]);
    }

    public function destroy(int $id)
    {
        $this->customerService->deleteCustomer($id);
        return response()->json(null, 204);
    }
}
