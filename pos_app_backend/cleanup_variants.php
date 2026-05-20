<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Scanning for duplicate variants...\n";

$products = Product::with('variants')->get();

foreach ($products as $product) {
    $variants = $product->variants;
    $seen = [];
    $toDelete = [];

    foreach ($variants as $variant) {
        $key = $variant->size_name;
        if (isset($seen[$key])) {
            // Keep the one with the higher ID or just the first found?
            // Let's keep the one that likely has orders if possible, or just the first one.
            echo "Found duplicate variant '{$key}' for product '{$product->name}' (ID: {$variant->id}). Marking for deletion.\n";
            $toDelete[] = $variant->id;
        } else {
            $seen[$key] = $variant->id;
        }
    }

    if (!empty($toDelete)) {
        ProductVariant::whereIn('id', $toDelete)->delete();
        echo "Deleted " . count($toDelete) . " duplicate variants for product '{$product->name}'.\n";
    }
}

echo "Cleanup complete.\n";
