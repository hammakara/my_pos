<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CafeTable;
use App\Events\OrderUpdated;
use Illuminate\Support\Facades\DB;

class OrderService extends BaseService
{
    public function createOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            $total = 0;
            foreach ($data['items'] as $item) {
                $total += ($item['unit_price'] * $item['quantity']);
            }

            $order = Order::create([
                'user_id'  => auth()->id(),
                'table_id' => $data['table_id'] ?? null,
                'type'     => $data['type'] ?? 'dine_in',
                'total'    => $total,
                'status'   => 'pending',
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create($item);
            }

            if ($order->table_id) {
                CafeTable::find($order->table_id)->update(['status' => 'occupied']);
            }

            OrderUpdated::dispatch($order);
            return $order->load(['items', 'table']);
        });
    }

    public function updateStatus(int $orderId, string $status)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => $status]);
        
        OrderUpdated::dispatch($order);
        return $order;
    }
}
