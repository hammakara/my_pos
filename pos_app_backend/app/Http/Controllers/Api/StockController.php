<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function movements(Request $request)
    {
        $movements = StockMovement::with('productVariant.product', 'user')
            ->when($request->product_variant_id, fn($q) => $q->where('product_variant_id', $request->product_variant_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->reason, fn($q) => $q->where('reason', $request->reason))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($movements);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'type'               => 'required|in:in,out',
            'quantity'           => 'required|integer|min:1',
            'reason'             => 'required|in:purchase,adjustment,return,damage,expired,transfer',
            'notes'              => 'nullable|string',
        ]);

        return DB::transaction(function () use ($data) {
            $variant = ProductVariant::findOrFail($data['product_variant_id']);

            if ($data['type'] === 'out' && $variant->stock_qty < $data['quantity']) {
                return response()->json(['message' => 'Insufficient stock'], 422);
            }

            if ($data['type'] === 'in') {
                $variant->increment('stock_qty', $data['quantity']);
            } else {
                $variant->decrement('stock_qty', $data['quantity']);
            }

            $movement = StockMovement::create([
                'product_variant_id' => $data['product_variant_id'],
                'type'               => $data['type'],
                'quantity'           => $data['quantity'],
                'reason'             => $data['reason'],
                'notes'              => $data['notes'] ?? null,
                'user_id'            => auth()->id(),
            ]);

            return response()->json([
                'data'            => $movement->load('productVariant.product', 'user'),
                'new_stock_qty'   => $variant->fresh()->stock_qty,
            ], 201);
        });
    }

    public function lowStock(Request $request)
    {
        $threshold = $request->threshold ?? 10;
        $variants = $this->stockService->getLowStock();
        
        return response()->json(['data' => $variants]);
    }
}
