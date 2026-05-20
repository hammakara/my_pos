<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'points_per_currency',
        'redemption_rate',
        'min_points_to_redeem',
        'expiry_months',
        'status',
        'description',
    ];

    protected $casts = [
        'points_per_currency' => 'decimal:2',
        'redemption_rate' => 'decimal:2',
        'min_points_to_redeem' => 'integer',
        'expiry_months' => 'integer',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function loyaltyRewards(): HasMany
    {
        return $this->hasMany(LoyaltyReward::class);
    }

    public function calculatePoints(float $amount): int
    {
        return (int) floor($amount * $this->points_per_currency);
    }

    public function calculateRedemptionValue(int $points): float
    {
        return $points * $this->redemption_rate;
    }
}
