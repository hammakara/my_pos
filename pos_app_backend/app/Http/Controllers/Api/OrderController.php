<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'table_id' => 'nullable|exists:cafe_tables,id',
            'type'     => 'required|in:dine_in,takeaway',
            'items'    => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_price'         => 'required|numeric',
            'items.*.subtotal'           => 'required|numeric',
        ]);

        $order = $this->orderService->createOrder($data);
        return new OrderResource($order);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => 'required|in:pending,cooking,ready,served,paid']);
        $order = $this->orderService->updateStatus($id, $data['status']);
        return new OrderResource($order);
    }
}
