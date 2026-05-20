<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerLoyalty;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyReward;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LoyaltyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $programs = LoyaltyProgram::with(['loyaltyRewards'])
            ->where('status', 'active')
            ->get();

        return response()->json([
            'data' => $programs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points_per_currency' => 'required|numeric|min:0',
            'redemption_rate' => 'required|numeric|min:0',
            'min_points_to_redeem' => 'required|integer|min:0',
            'expiry_months' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $program = LoyaltyProgram::create($validated);

        return response()->json([
            'data' => $program->load('loyaltyRewards'),
            'message' => 'Loyalty program created successfully',
        ], 201);
    }

    public function show(Request $request, LoyaltyProgram $program): JsonResponse
    {
        $program->load(['loyaltyRewards', 'customers']);

        return response()->json([
            'data' => $program,
        ]);
    }

    public function update(Request $request, LoyaltyProgram $program): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'points_per_currency' => 'sometimes|numeric|min:0',
            'redemption_rate' => 'sometimes|numeric|min:0',
            'min_points_to_redeem' => 'sometimes|integer|min:0',
            'expiry_months' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $program->update($validated);

        return response()->json([
            'data' => $program->load('loyaltyRewards'),
            'message' => 'Loyalty program updated successfully',
        ]);
    }

    public function destroy(Request $request, LoyaltyProgram $program): JsonResponse
    {
        $program->delete();

        return response()->json([
            'message' => 'Loyalty program deleted successfully',
        ]);
    }

    public function enrollCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'loyalty_program_id' => 'required|exists:loyalty_programs,id',
        ]);

        $existing = CustomerLoyalty::where([
            'customer_id' => $validated['customer_id'],
            'loyalty_program_id' => $validated['loyalty_program_id'],
        ])->first();

        if ($existing) {
            return response()->json([
                'message' => 'Customer already enrolled in this program',
            ], 400);
        }

        $customerLoyalty = CustomerLoyalty::create([
            ...$validated,
            'joined_date' => now(),
            'last_activity_date' => now(),
        ]);

        return response()->json([
            'data' => $customerLoyalty->load(['customer', 'loyaltyProgram']),
            'message' => 'Customer enrolled successfully',
        ], 201);
    }

    public function getCustomerPoints(Request $request, $customerId): JsonResponse
    {
        $customerLoyalty = CustomerLoyalty::where('customer_id', $customerId)
            ->with(['loyaltyProgram', 'customer'])
            ->first();

        if (!$customerLoyalty) {
            return response()->json([
                'message' => 'Customer not enrolled in any loyalty program',
            ], 404);
        }

        return response()->json([
            'data' => [
                'points_balance' => $customerLoyalty->points_balance,
                'tier_level' => $customerLoyalty->tier_level,
                'total_spent' => $customerLoyalty->total_spent,
                'program' => $customerLoyalty->loyaltyProgram,
            ],
        ]);
    }

    public function addPoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'loyalty_program_id' => 'required|exists:loyalty_programs,id',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $program = LoyaltyProgram::find($validated['loyalty_program_id']);
        $points = $program->calculatePoints($validated['order_amount']);

        $customerLoyalty = CustomerLoyalty::where([
            'customer_id' => $validated['customer_id'],
            'loyalty_program_id' => $validated['loyalty_program_id'],
        ])->first();

        if (!$customerLoyalty) {
            return response()->json([
                'message' => 'Customer not enrolled in this program',
            ], 404);
        }

        $customerLoyalty->addPoints($points);
        $customerLoyalty->total_spent += $validated['order_amount'];
        $customerLoyalty->updateTier();

        return response()->json([
            'data' => [
                'points_added' => $points,
                'new_balance' => $customerLoyalty->points_balance,
                'tier_level' => $customerLoyalty->tier_level,
            ],
            'message' => 'Points added successfully',
        ]);
    }

    public function redeemPoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'loyalty_program_id' => 'required|exists:loyalty_programs,id',
            'points' => 'required|integer|min:1',
        ]);

        $customerLoyalty = CustomerLoyalty::where([
            'customer_id' => $validated['customer_id'],
            'loyalty_program_id' => $validated['loyalty_program_id'],
        ])->first();

        if (!$customerLoyalty) {
            return response()->json([
                'message' => 'Customer not enrolled in this program',
            ], 404);
        }

        $program = $customerLoyalty->loyaltyProgram;
        if ($validated['points'] < $program->min_points_to_redeem) {
            return response()->json([
                'message' => "Minimum {$program->min_points_to_redeem} points required to redeem",
            ], 400);
        }

        $redeemed = $customerLoyalty->redeemPoints($validated['points']);

        if (!$redeemed) {
            return response()->json([
                'message' => 'Insufficient points balance',
            ], 400);
        }

        $redemptionValue = $program->calculateRedemptionValue($validated['points']);

        return response()->json([
            'data' => [
                'points_redeemed' => $validated['points'],
                'redemption_value' => $redemptionValue,
                'new_balance' => $customerLoyalty->points_balance,
            ],
            'message' => 'Points redeemed successfully',
        ]);
    }
}
