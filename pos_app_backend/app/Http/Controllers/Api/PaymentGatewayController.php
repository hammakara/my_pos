<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentGatewayController extends Controller
{
    protected $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->paymentGatewayService->getAllGateways()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string|in:stripe,paypal,custom,khqr',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'environment' => 'required|in:sandbox,production',
            'currency' => 'required|string|max:3',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $gateway = $this->paymentGatewayService->createGateway($validated);

        return response()->json([
            'data' => $gateway,
            'message' => 'Payment gateway configured successfully',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->paymentGatewayService->getGatewayById($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'api_key' => 'sometimes|string',
            'api_secret' => 'sometimes|string',
            'environment' => 'sometimes|in:sandbox,production',
            'currency' => 'sometimes|string|max:3',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $gateway = $this->paymentGatewayService->updateGateway($id, $validated);

        return response()->json([
            'data' => $gateway,
            'message' => 'Payment gateway updated successfully',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->paymentGatewayService->deleteGateway($id);
        return response()->json(['message' => 'Payment gateway deleted successfully']);
    }

    public function testConnection(Request $request, int $id): JsonResponse
    {
        $result = $this->paymentGatewayService->testConnection($id);
        return response()->json(['data' => $result]);
    }
}
