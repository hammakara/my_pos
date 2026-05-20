<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class BudgetService extends BaseService
{
    public function createBudget(array $data): Budget
    {
        return Budget::create($data);
    }

    public function updateBudget(Budget $budget, array $data): Budget
    {
        $budget->update($data);
        return $budget->fresh();
    }

    public function deleteBudget(Budget $budget): bool
    {
        return $budget->delete();
    }

    public function trackOrderAgainstBudget(Order $order): void
    {
        $activeBudgets = Budget::where('user_id', $order->user_id)
            ->where('status', 'active')
            ->where('start_date', '<=', $order->created_at)
            ->where(function ($query) use ($order) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $order->created_at);
            })
            ->get();

        foreach ($activeBudgets as $budget) {
            // Check if order category matches budget category (if specified)
            if ($budget->category_id && $order->items) {
                $orderCategory = $order->items->first()?->productVariant?->product?->category_id;
                if ($orderCategory != $budget->category_id) {
                    continue;
                }
            }

            BudgetTransaction::create([
                'budget_id' => $budget->id,
                'order_id' => $order->id,
                'amount' => $order->total,
                'type' => 'debit',
                'description' => "Order #{$order->id}",
                'transaction_date' => $order->created_at->toDateString(),
            ]);

            // Check if budget is over limit
            if ($budget->isOverBudget()) {
                // Trigger alert (could send notification)
                $this->triggerBudgetAlert($budget);
            }
        }
    }

    private function triggerBudgetAlert(Budget $budget): void
    {
        // Implement alert logic (email, notification, etc.)
        // This is a placeholder for notification system
    }

    public function getBudgetStatistics(Budget $budget): array
    {
        return [
            'spent_amount' => $budget->getSpentAmount(),
            'remaining_amount' => $budget->getRemainingAmount(),
            'usage_percentage' => $budget->getUsagePercentage(),
            'is_over_budget' => $budget->isOverBudget(),
            'is_near_limit' => $budget->isNearLimit(),
            'transaction_count' => $budget->budgetTransactions()->count(),
        ];
    }

    public function getUserBudgetSummary(int $userId): array
    {
        $budgets = Budget::where('user_id', $userId)
            ->where('status', 'active')
            ->get();

        $totalBudget = $budgets->sum('amount');
        $totalSpent = $budgets->sum(fn($b) => $b->getSpentAmount());
        $overBudgetCount = $budgets->filter(fn($b) => $b->isOverBudget())->count();
        $nearLimitCount = $budgets->filter(fn($b) => $b->isNearLimit())->count();

        return [
            'total_budgets' => $budgets->count(),
            'total_budget_amount' => $totalBudget,
            'total_spent' => $totalSpent,
            'total_remaining' => $totalBudget - $totalSpent,
            'over_budget_count' => $overBudgetCount,
            'near_limit_count' => $nearLimitCount,
            'budgets' => $budgets,
        ];
    }
}
