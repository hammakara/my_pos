<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function dashboard(Request $request)
    {
        return response()->json($this->reportService->getDashboardData());
    }

    public function sales(Request $request)
    {
        $filters = [
            'start_date' => $request->from ?? null,
            'end_date' => $request->to ?? null,
            'status' => $request->status ?? null,
        ];
        return response()->json($this->reportService->getSalesReport($filters));
    }

    public function products(Request $request)
    {
        $filters = [
            'category_id' => $request->category_id ?? null,
            'status' => $request->status ?? null,
        ];
        return response()->json($this->reportService->getProductsReport($filters));
    }

    public function inventory(Request $request)
    {
        return response()->json(['data' => $this->reportService->getInventoryReport()]);
    }
}
