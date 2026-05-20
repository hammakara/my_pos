<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLoyalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'loyalty_program_id',
        'points_balance',
        'tier_level',
        'total_spent',
        'joined_date',
        'last_activity_date',
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'total_spent' => 'decimal:2',
        'joined_date' => 'date',
        'last_activity_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function addPoints(int $points): void
    {
        $this->points_balance += $points;
        $this->last_activity_date = now();
        $this->save();
    }

    public function redeemPoints(int $points): bool
    {
        if ($this->points_balance < $points) {
            return false;
        }

        $this->points_balance -= $points;
        $this->last_activity_date = now();
        $this->save();

        return true;
    }

    public function updateTier(): void
    {
        // Implement tier logic based on total_spent
        if ($this->total_spent >= 10000) {
            $this->tier_level = 'platinum';
        } elseif ($this->total_spent >= 5000) {
            $this->tier_level = 'gold';
        } elseif ($this->total_spent >= 1000) {
            $this->tier_level = 'silver';
        } else {
            $this->tier_level = 'bronze';
        }
        $this->save();
    }
}
