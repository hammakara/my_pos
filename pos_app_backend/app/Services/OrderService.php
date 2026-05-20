<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CafeTable;
use App\Models\ProductVariant;
use App\Events\OrderUpdated;
use Illuminate\Support\Facades\DB;
use App\Services\TelegramService;

class OrderService extends BaseService
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function createOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Calculate totals
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += ($item['unit_price'] * $item['quantity']);
            }

            $tax = $data['tax'] ?? ($subtotal * 0.10); // 10% tax
            $discount = $data['discount'] ?? 0;
            $total = $subtotal + $tax - $discount;

            // Calculate change for cash payments
            $changeAmount = null;
            if (isset($data['payment_method']) && $data['payment_method'] === 'cash' && isset($data['paid_amount'])) {
                $changeAmount = $data['paid_amount'] - $total;
            }

            // Determine if the order has ONLY drinks/beverages
            $hasOnlyDrinks = true;
            $drinkKeywords = [
                "coffee", "drink", "beverage", "tea", "juice", "soda", "milk", "water", 
                "beer", "wine", "smoothie", "latte", "espresso", "cappuccino", "macchiato", 
                "mocha", "matcha", "shake", "late", "កាហ្វេ", "តែ", "ទឹក"
            ];

            foreach ($data['items'] as $item) {
                $variant = ProductVariant::with(['product.category'])->find($item['product_variant_id']);
                if ($variant && $variant->product) {
                    $productName = strtolower($variant->product->name);
                    $categoryName = $variant->product->category ? strtolower($variant->product->category->name) : '';
                    $categorySlug = $variant->product->category ? strtolower($variant->product->category->slug) : '';

                    $isDrink = false;
                    foreach ($drinkKeywords as $keyword) {
                        if (str_contains($productName, $keyword) || 
                            str_contains($categoryName, $keyword) || 
                            str_contains($categorySlug, $keyword)) {
                            $isDrink = true;
                            break;
                        }
                    }

                    if (!$isDrink) {
                        $hasOnlyDrinks = false;
                        break;
                    }
                } else {
                    $hasOnlyDrinks = false;
                    break;
                }
            }

            $initialStatus = 'pending';
            if ($data['payment_method'] !== 'pending' && $hasOnlyDrinks) {
                $initialStatus = isset($data['paid_amount']) ? 'paid' : 'ready';
            }

            // Create order
            $order = Order::create([
                'user_id'        => auth()->id() ?? \App\Models\User::first()->id ?? 1,
                'table_id'       => $data['table_id'] ?? null,
                'type'           => $data['type'] ?? 'dine_in',
                'total'          => $total,
                'tax'            => $tax,
                'discount'       => $discount,
                'status'         => $initialStatus,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_status' => ($data['payment_method'] === 'pending') ? 'pending' : (isset($data['paid_amount']) ? 'paid' : 'pending'),
                'paid_amount'    => ($data['payment_method'] === 'pending') ? 0 : ($data['paid_amount'] ?? null),
                'change_amount'  => ($data['payment_method'] === 'pending') ? 0 : $changeAmount,
                'paid_at'        => ($data['payment_method'] !== 'pending' && isset($data['paid_amount'])) ? now() : null,
            ]);

            // Create payment record if paid
            if (isset($data['paid_amount']) && $data['paid_amount'] > 0 && $data['payment_method'] !== 'pending') {
                $gatewayName = null;
                if (isset($data['gateway_id'])) {
                    $gw = \App\Models\PaymentGateway::find($data['gateway_id']);
                    if ($gw) {
                        $gatewayName = $gw->name;
                    }
                }

                \App\Models\Payment::create([
                    'order_id'       => $order->id,
                    'payment_number' => 'PAY-' . strtoupper(uniqid()),
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'amount'         => $total,
                    'currency'       => 'USD',
                    'status'         => 'completed',
                    'gateway'        => $gatewayName ?? ($data['payment_method'] === 'card' ? 'Terminal Simulation' : null),
                    'paid_at'        => now(),
                    'user_id'        => auth()->id() ?? $order->user_id ?? 1,
                ]);
            }

            // Create order items and deduct stock
            foreach ($data['items'] as $item) {
                $order->items()->create($item);

                // Deduct stock quantity
                $variant = ProductVariant::find($item['product_variant_id']);
                if ($variant && $variant->stock_qty !== null) {
                    $variant->decrement('stock_qty', $item['quantity']);
                }
            }

            // Update table status if dine-in
            if ($order->table_id) {
                CafeTable::find($order->table_id)->update(['status' => 'occupied']);
            }

            OrderUpdated::dispatch($order);
            $this->telegramService->sendOrderNotification($order);
            return $order->load(['items.variant.product.category', 'table', 'user']);
        });
    }

    public function updateOrderItems(int $orderId, array $data)
    {
        return DB::transaction(function () use ($orderId, $data) {
            $order = Order::findOrFail($orderId);

            // 1. Restore stock for current items
            foreach ($order->items as $item) {
                $variant = ProductVariant::find($item->product_variant_id);
                if ($variant && $variant->stock_qty !== null) {
                    $variant->increment('stock_qty', $item->quantity);
                }
            }

            // 2. Clear old items
            $order->items()->delete();

            // 3. Create new items and deduct stock
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $order->items()->create($item);
                $subtotal += $item['subtotal'];

                $variant = ProductVariant::find($item['product_variant_id']);
                if ($variant && $variant->stock_qty !== null) {
                    $variant->decrement('stock_qty', $item['quantity']);
                }
            }

            // 4. Update totals
            $tax = $subtotal * 0.10; // Assuming 10% tax
            $order->update([
                'subtotal' => $subtotal, // Note: Order model might need this field if it doesn't exist
                'tax'      => $tax,
                'total'    => $subtotal + $tax - $order->discount,
            ]);

            OrderUpdated::dispatch($order);
            return $order->load(['items.variant.product.category', 'table', 'user']);
        });
    }

    public function updateStatus(int $orderId, string $status)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => $status]);

        // Free table if order is completed/paid
        if (in_array($status, ['paid', 'completed']) && $order->table_id) {
            CafeTable::find($order->table_id)->update(['status' => 'available']);
        }

        OrderUpdated::dispatch($order);
        return $order->load(['items.variant.product.category', 'table', 'user']);
    }

    public function updatePaymentStatus(int $orderId, array $data)
    {
        $order = Order::findOrFail($orderId);

        $updateData = [
            'payment_status' => $data['payment_status'],
        ];

        if (!empty($data['payment_method'])) {
            $updateData['payment_method'] = $data['payment_method'];
        }

        if ($data['payment_status'] === 'paid') {
            $updateData['paid_amount'] = $data['paid_amount'] ?? $order->total;
            $updateData['change_amount'] = $data['change_amount'] ?? null;
            $updateData['paid_at'] = now();

            // Also update order status to paid
            $updateData['status'] = 'paid';

            // Free table if dine-in
            if ($order->table_id) {
                CafeTable::find($order->table_id)->update(['status' => 'available']);
            }

            // Create payment record
            \App\Models\Payment::create([
                'order_id'       => $order->id,
                'payment_number' => 'PAY-' . strtoupper(uniqid()),
                'payment_method' => in_array($updateData['payment_method'] ?? $order->payment_method ?? '', ['cash', 'card', 'digital_wallet', 'bank_transfer', 'credit', 'gift_card', 'check']) ? ($updateData['payment_method'] ?? $order->payment_method ?? 'cash') : 'cash',
                'amount'         => $updateData['paid_amount'],
                'currency'       => 'USD',
                'status'         => 'completed',
                'paid_at'        => now(),
                'user_id'        => auth()->id() ?? $order->user_id ?? 1,
            ]);
        }

        $order->update($updateData);

        OrderUpdated::dispatch($order);
        if ($updateData['payment_status'] === 'paid') {
            $this->telegramService->sendOrderNotification($order, 'Order Paid');
        }
        return $order->load(['items.variant.product.category', 'table', 'user']);
    }

    public function getAllOrders($perPage = 15)
    {
        return Order::with(['items.variant.product.category', 'table', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getOrderById(int $orderId)
    {
        return Order::with(['items.variant.product.category', 'table', 'user'])
            ->findOrFail($orderId);
    }

    public function cancelOrder(int $orderId)
    {
        return DB::transaction(function () use ($orderId) {
            $order = Order::findOrFail($orderId);

            // Restore stock quantities
            foreach ($order->items as $item) {
                $variant = ProductVariant::find($item->product_variant_id);
                if ($variant && $variant->stock_qty !== null) {
                    $variant->increment('stock_qty', $item->quantity);
                }
            }

            // Update order status
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);

            // Free table if occupied
            if ($order->table_id) {
                CafeTable::find($order->table_id)->update(['status' => 'available']);
            }

            OrderUpdated::dispatch($order);
            return $order->load(['items.variant.product.category', 'table', 'user']);
        });
    }
}
