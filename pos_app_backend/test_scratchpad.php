<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\OrderService;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

// Simple scratchpad for verifying OrderService logic
try {
    echo "Testing OrderService...\n";
    $orderService = app(OrderService::class);

    // 1. Create a dummy order
    $data = [
        'user_id' => 1,
        'payment_method' => 'cash',
        'items' => [
            ['product_variant_id' => 1, 'quantity' => 2, 'unit_price' => 10.00, 'subtotal' => 20.00]
        ],
        'total' => 20.00
    ];

    $order = $orderService->createOrder($data);
    echo "Created Order ID: {$order->id}\n";

    // 2. Update status
    $orderService->updateStatus($order->id, 'served');
    echo "Updated Order Status to 'served'\n";

    // 3. Cleanup
    $order->items()->delete();
    $order->delete();
    echo "Cleanup successful.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
