<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class ReportService extends BaseService
{
    public function getDashboardData()
    {
        $today = now()->today();
        $last7Days = now()->subDays(7);

        // Daily sales for last 7 days
        $dailySales = Order::where('created_at', '>=', $last7Days)
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'revenue' => (float) $item->revenue,
                ];
            });

        $topProducts = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', $last7Days)
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'products.name as product_name',
                DB::raw('SUM(order_items.quantity) as total_qty')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'total_qty' => (int) $item->total_qty,
                ];
            });

        return [
            'daily_sales' => $dailySales,
            'top_products' => $topProducts,
        ];
    }

    public function getSalesReport(array $filters = [])
    {
        $query = Order::with('items');

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate(15);
    }

    public function getProductsReport(array $filters = [])
    {
        $query = Product::with('variants');

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15);
    }

    public function getInventoryReport()
    {
        return ProductVariant::with('product')
            ->where('stock_qty', '<', 10)
            ->orderBy('stock_qty')
            ->get();
    }
}
