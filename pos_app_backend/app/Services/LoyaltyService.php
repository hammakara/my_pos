<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLoyalty;
use App\Models\LoyaltyProgram;
use App\Models\Order;

class LoyaltyService extends BaseService
{
    public function enrollCustomer(Customer $customer, LoyaltyProgram $program): CustomerLoyalty
    {
        return CustomerLoyalty::firstOrCreate([
            'customer_id' => $customer->id,
            'loyalty_program_id' => $program->id,
        ], [
            'joined_date' => now(),
            'last_activity_date' => now(),
        ]);
    }

    public function addPointsForOrder(Order $order): void
    {
        if (!$order->customer_id) {
            return;
        }

        $customerLoyalty = CustomerLoyalty::where('customer_id', $order->customer_id)
            ->with('loyaltyProgram')
            ->first();

        if (!$customerLoyalty) {
            return;
        }

        $program = $customerLoyalty->loyaltyProgram;
        $points = $program->calculatePoints($order->total);

        $customerLoyalty->addPoints($points);
        $customerLoyalty->total_spent += $order->total;
        $customerLoyalty->updateTier();
    }

    public function redeemPoints(CustomerLoyalty $customerLoyalty, int $points): array
    {
        $program = $customerLoyalty->loyaltyProgram;

        if ($points < $program->min_points_to_redeem) {
            return [
                'success' => false,
                'message' => "Minimum {$program->min_points_to_redeem} points required",
            ];
        }

        $redeemed = $customerLoyalty->redeemPoints($points);

        if (!$redeemed) {
            return [
                'success' => false,
                'message' => 'Insufficient points balance',
            ];
        }

        $redemptionValue = $program->calculateRedemptionValue($points);

        return [
            'success' => true,
            'points_redeemed' => $points,
            'redemption_value' => $redemptionValue,
            'new_balance' => $customerLoyalty->points_balance,
        ];
    }

    public function getCustomerLoyaltyStatus(Customer $customer): array
    {
        $customerLoyalties = CustomerLoyalty::where('customer_id', $customer->id)
            ->with('loyaltyProgram')
            ->get();

        return [
            'enrolled_programs' => $customerLoyalties->count(),
            'total_points' => $customerLoyalties->sum('points_balance'),
            'highest_tier' => $this->getHighestTier($customerLoyalties),
            'programs' => $customerLoyalties,
        ];
    }

    private function getHighestTier($customerLoyalties): string
    {
        $tiers = ['bronze', 'silver', 'gold', 'platinum'];
        $highestTier = 'bronze';

        foreach ($customerLoyalties as $cl) {
            $currentTierIndex = array_search($cl->tier_level, $tiers);
            $highestTierIndex = array_search($highestTier, $tiers);
            if ($currentTierIndex > $highestTierIndex) {
                $highestTier = $cl->tier_level;
            }
        }

        return $highestTier;
    }

    public function calculateTierBenefits(string $tier): array
    {
        $benefits = [
            'bronze' => [
                'points_multiplier' => 1.0,
                'discount_percentage' => 0,
                'free_shipping' => false,
            ],
            'silver' => [
                'points_multiplier' => 1.2,
                'discount_percentage' => 5,
                'free_shipping' => false,
            ],
            'gold' => [
                'points_multiplier' => 1.5,
                'discount_percentage' => 10,
                'free_shipping' => true,
            ],
            'platinum' => [
                'points_multiplier' => 2.0,
                'discount_percentage' => 15,
                'free_shipping' => true,
            ],
        ];

        return $benefits[$tier] ?? $benefits['bronze'];
    }
}
