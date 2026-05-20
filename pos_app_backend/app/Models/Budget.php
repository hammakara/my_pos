<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'period',
        'start_date',
        'end_date',
        'category_id',
        'status',
        'alert_threshold',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'alert_threshold' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function budgetTransactions()
    {
        return $this->hasMany(BudgetTransaction::class);
    }

    public function getSpentAmount(): float
    {
        // Calculate spent amount based on orders within budget period
        $query = Order::where('user_id', $this->user_id)
            ->where('created_at', '>=', $this->start_date);
            
        if ($this->end_date) {
            $query->where('created_at', '<=', $this->end_date);
        }
        
        return (float) $query->sum('total');
    }

    public function getRemainingAmount(): float
    {
        return $this->amount - $this->getSpentAmount();
    }

    public function getUsagePercentage(): float
    {
        if ($this->amount == 0) return 0;
        return ($this->getSpentAmount() / $this->amount) * 100;
    }

    public function isOverBudget(): bool
    {
        return $this->getSpentAmount() > $this->amount;
    }

    public function isNearLimit(): bool
    {
        return $this->getRemainingAmount() <= $this->alert_threshold;
    }
}
