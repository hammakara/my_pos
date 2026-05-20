<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    protected $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    public function index(Request $request)
    {
        $orders = PurchaseOrder::with('supplier')
            ->when($request->search, fn($q) => $q->where('po_number', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'order_date'     => 'required|date',
            'expected_date'  => 'nullable|date',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_cost'         => 'required|numeric|min:0',
        ]);

        $order = $this->purchaseOrderService->createOrder($data);
        return response()->json(['data' => $order], 201);
    }

    public function show(int $id)
    {
        return response()->json(['data' => $this->purchaseOrderService->getOrderById($id)]);
    }

    public function update(Request $request, int $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status === 'received') {
            return response()->json(['message' => 'Cannot edit a received purchase order'], 422);
        }

        $data = $request->validate([
            'supplier_id'   => 'sometimes|required|exists:suppliers,id',
            'order_date'    => 'sometimes|required|date',
            'expected_date' => 'nullable|date',
            'notes'         => 'nullable|string',
            'status'        => 'sometimes|in:draft,sent,cancelled',
        ]);

        $order = $this->purchaseOrderService->updateOrder($id, $data);
        return response()->json(['data' => $order]);
    }

    public function receive(Request $request, int $id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        if ($po->status === 'received') {
            return response()->json(['message' => 'Already received'], 422);
        }

        $data = $request->validate([
            'items'                      => 'required|array',
            'items.*.id'                 => 'required|exists:purchase_order_items,id',
            'items.*.received_quantity'  => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($po, $data) {
            foreach ($data['items'] as $itemData) {
                $item = PurchaseOrderItem::findOrFail($itemData['id']);
                $item->update(['received_quantity' => $itemData['received_quantity']]);

                if ($itemData['received_quantity'] > 0) {
                    // Update stock
                    $variant = ProductVariant::findOrFail($item->product_variant_id);
                    $variant->increment('stock_qty', $itemData['received_quantity']);

                    // Record stock movement
                    StockMovement::create([
                        'product_variant_id' => $item->product_variant_id,
                        'type'               => 'in',
                        'quantity'           => $itemData['received_quantity'],
                        'reason'             => 'purchase',
                        'reference_type'     => 'purchase_order',
                        'reference_id'       => $po->id,
                        'notes'              => "Received from PO #{$po->po_number}",
                        'user_id'            => auth()->id(),
                    ]);
                }
            }

            $po->update([
                'status'        => 'received',
                'received_date' => now()->toDateString(),
            ]);

            return response()->json(['data' => $po->load('items.productVariant', 'supplier')]);
        });
    }

    public function destroy(int $id)
    {
        $po = PurchaseOrder::findOrFail($id);
        if ($po->status === 'received') {
            return response()->json(['message' => 'Cannot delete a received purchase order'], 422);
        }
        $this->purchaseOrderService->deleteOrder($id);
        return response()->json(null, 204);
    }
}
