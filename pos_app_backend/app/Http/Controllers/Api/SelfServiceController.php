<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Order;
use App\Services\OrderService;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;

class SelfServiceController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Fetch the complete menu (active categories with active products and variants).
     */
    public function getMenu()
    {
        $categories = Category::where('status', 'active')
            ->with(['products' => function ($q) {
                $q->where('status', 'active')->with('variants');
            }])
            ->get();

        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Fetch a specific table detail (confirming the table number on customer's phone).
     */
    public function getTable(int $id)
    {
        $table = CafeTable::findOrFail($id);
        return response()->json([
            'table' => $table
        ]);
    }

    /**
     * Customer places a self-service order at a table (dine-in, pay-later).
     */
    public function placeOrder(Request $request)
    {
        $data = $request->validate([
            'table_id'                   => 'required|exists:cafe_tables,id',
            'items'                      => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_price'         => 'required|numeric|min:0',
            'items.*.subtotal'           => 'required|numeric|min:0',
            'discount'                   => 'nullable|numeric|min:0',
        ]);

        // Default configurations for guest self-service orders
        $data['type'] = 'dine_in';
        $data['payment_method'] = 'cash'; // Tab remains open on this table to be settled cash/card later at registry
        $data['discount'] = $data['discount'] ?? 0;

        // Create the order using OrderService
        $order = $this->orderService->createOrder($data);

        return new OrderResource($order);
    }

    /**
     * Customer tracks order preparation status.
     */
    public function trackOrder(int $id)
    {
        $order = $this->orderService->getOrderById($id);
        return new OrderResource($order);
    }
}
