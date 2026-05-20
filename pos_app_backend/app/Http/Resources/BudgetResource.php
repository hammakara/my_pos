<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'amount' => (float) $this->amount,
            'period' => $this->period,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'category_id' => $this->category_id,
            'status' => $this->status,
            'alert_threshold' => (float) $this->alert_threshold,
            'spent_amount' => $this->getSpentAmount(),
            'remaining_amount' => $this->getRemainingAmount(),
            'usage_percentage' => $this->getUsagePercentage(),
            'is_over_budget' => $this->isOverBudget(),
            'is_near_limit' => $this->isNearLimit(),
            'category' => $this->whenLoaded('category'),
            'user' => $this->whenLoaded('user'),
            'transactions' => $this->whenLoaded('budgetTransactions'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
