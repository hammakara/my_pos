<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\BudgetTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $budgets = Budget::where('user_id', $request->user()->id)
            ->with(['category', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => BudgetResource::collection($budgets),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'period' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'category_id' => 'nullable|exists:categories,id',
            'alert_threshold' => 'nullable|numeric|min:0',
        ]);

        $budget = Budget::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => 'active',
        ]);

        return response()->json([
            'data' => new BudgetResource($budget->load(['category', 'user'])),
            'message' => 'Budget created successfully',
        ], 201);
    }

    public function show(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $budget->load(['category', 'user', 'budgetTransactions']);

        return response()->json([
            'data' => new BudgetResource($budget),
        ]);
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'period' => 'sometimes|in:daily,weekly,monthly,yearly',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'sometimes|in:active,inactive,completed',
            'alert_threshold' => 'nullable|numeric|min:0',
        ]);

        $budget->update($validated);

        return response()->json([
            'data' => new BudgetResource($budget->load(['category', 'user'])),
            'message' => 'Budget updated successfully',
        ]);
    }

    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $budget->delete();

        return response()->json([
            'message' => 'Budget deleted successfully',
        ]);
    }

    public function getStatistics(Request $request, Budget $budget): JsonResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => [
                'spent_amount' => $budget->getSpentAmount(),
                'remaining_amount' => $budget->getRemainingAmount(),
                'usage_percentage' => $budget->getUsagePercentage(),
                'is_over_budget' => $budget->isOverBudget(),
                'is_near_limit' => $budget->isNearLimit(),
            ],
        ]);
    }
}
