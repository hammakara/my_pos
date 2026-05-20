<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class StockService extends BaseService
{
    public function getMovements($perPage = 15)
    {
        return StockMovement::with('productVariant.product', 'user')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function adjustStock(array $data)
    {
        return DB::transaction(function () use ($data) {
            $variant = ProductVariant::findOrFail($data['product_variant_id']);
            
            $oldQuantity = $variant->stock_qty ?? 0;
            $newQuantity = $data['quantity'];
            $difference = $newQuantity - $oldQuantity;

            $variant->update(['stock_qty' => $newQuantity]);

            // Record the movement
            StockMovement::create([
                'product_variant_id' => $variant->id,
                'quantity' => $difference,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'type' => $difference >= 0 ? 'adjustment_positive' : 'adjustment_negative',
                'reason' => $data['reason'] ?? 'Manual adjustment',
                'user_id' => auth()->id(),
            ]);

            return $variant;
        });
    }

    public function getLowStock()
    {
        return ProductVariant::with('product')
            ->where('stock_qty', '<', 10)
            ->orderBy('stock_qty')
            ->get();
    }

    public function recordMovement(array $data)
    {
        return StockMovement::create([
            'product_variant_id' => $data['product_variant_id'],
            'quantity' => $data['quantity'],
            'old_quantity' => $data['old_quantity'] ?? 0,
            'new_quantity' => $data['new_quantity'] ?? 0,
            'type' => $data['type'],
            'reason' => $data['reason'] ?? null,
            'user_id' => auth()->id(),
        ]);
    }
}
