<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService extends BaseService
{
    public function getAllOrders($perPage = 15)
    {
        return PurchaseOrder::with('supplier', 'items')->paginate($perPage);
    }

    public function getOrderById(int $id)
    {
        return PurchaseOrder::with('supplier', 'items')->findOrFail($id);
    }

    public function createOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'] ?? now(),
                'expected_date' => $data['expected_date'] ?? null,
                'status' => 'pending',
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $subtotal = $item['quantity'] * $item['unit_cost'];
                $totalAmount += $subtotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);
            return $order->load('supplier', 'items');
        });
    }

    public function updateOrder(int $id, array $data)
    {
        $order = PurchaseOrder::findOrFail($id);
        $order->update($data);
        return $order;
    }

    public function deleteOrder(int $id)
    {
        return PurchaseOrder::destroy($id);
    }

    public function receiveOrder(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $order = PurchaseOrder::with('items')->findOrFail($id);
            
            $order->update([
                'status' => 'received',
                'received_date' => now(),
            ]);

            // Update stock for each item
            foreach ($order->items as $item) {
                $variant = $item->productVariant;
                if ($variant) {
                    $variant->increment('stock_qty', $item->quantity);
                }
            }

            return $order->load('supplier', 'items');
        });
    }
}
