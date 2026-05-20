<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->withCount('purchaseOrders')
            ->paginate($request->per_page ?? 15);

        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string',
            'payment_terms'  => 'nullable|string|max:100',
            'tax_id'         => 'nullable|string|max:100',
            'status'         => 'nullable|in:active,inactive',
            'notes'          => 'nullable|string',
        ]);

        $supplier = $this->supplierService->createSupplier($data);
        return response()->json(['data' => $supplier], 201);
    }

    public function show(int $id)
    {
        $supplier = Supplier::withCount('purchaseOrders')->findOrFail($id);
        return response()->json(['data' => $supplier]);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name'           => 'sometimes|required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string',
            'payment_terms'  => 'nullable|string|max:100',
            'tax_id'         => 'nullable|string|max:100',
            'status'         => 'nullable|in:active,inactive',
            'notes'          => 'nullable|string',
        ]);

        $supplier = $this->supplierService->updateSupplier($id, $data);
        return response()->json(['data' => $supplier]);
    }

    public function destroy(int $id)
    {
        $this->supplierService->deleteSupplier($id);
        return response()->json(null, 204);
    }
}
