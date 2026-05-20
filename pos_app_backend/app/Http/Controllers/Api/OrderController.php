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

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $orders = $this->orderService->getAllOrders($perPage);
        return OrderResource::collection($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'table_id'                   => 'nullable|exists:cafe_tables,id',
            'type'                       => 'required|in:dine_in,takeaway',
            'payment_method'             => 'required|in:cash,card,digital_wallet,khqr,pending',
            'paid_amount'                => 'nullable|numeric|min:0',
            'discount'                   => 'nullable|numeric|min:0',
            'gateway_id'                 => 'nullable|exists:payment_gateways,id',
            'items'                      => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_price'         => 'required|numeric',
            'items.*.subtotal'           => 'required|numeric',
        ]);

        $order = $this->orderService->createOrder($data);
        return new OrderResource($order);
    }

    public function show(int $id)
    {
        $order = $this->orderService->getOrderById($id);
        return new OrderResource($order);
    }

    public function updateItems(Request $request, int $id)
    {
        $data = $request->validate([
            'items'                      => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_price'         => 'required|numeric',
            'items.*.subtotal'           => 'required|numeric',
        ]);

        $order = $this->orderService->updateOrderItems($id, $data);
        return new OrderResource($order);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,cooking,ready,served,paid,completed,cancelled'
        ]);
        
        $order = $this->orderService->updateStatus($id, $data['status']);
        return new OrderResource($order);
    }

    public function updatePayment(Request $request, int $id)
    {
        $data = $request->validate([
            'payment_status' => 'required|in:pending,paid,refunded,cancelled',
            'payment_method' => 'nullable|in:cash,card,digital_wallet,khqr,pending',
            'paid_amount'    => 'nullable|numeric|min:0',
            'change_amount'  => 'nullable|numeric|min:0',
        ]);

        $order = $this->orderService->updatePaymentStatus($id, $data);
        return new OrderResource($order);
    }

    public function cancel(int $id)
    {
        $order = $this->orderService->cancelOrder($id);
        return new OrderResource($order);
    }
}
