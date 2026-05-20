<?php

namespace App\Services;

use App\Models\PaymentGateway;

class PaymentGatewayService extends BaseService
{
    public function getAllGateways()
    {
        return PaymentGateway::all();
    }

    public function createGateway(array $data)
    {
        return PaymentGateway::create($data);
    }

    public function getGatewayById(int $id)
    {
        return PaymentGateway::findOrFail($id);
    }

    public function updateGateway(int $id, array $data)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->update($data);
        return $gateway;
    }

    public function deleteGateway(int $id)
    {
        return PaymentGateway::destroy($id);
    }

    public function testConnection(int $id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        // Implement actual connection test based on gateway type
        
        $connected = false;
        
        try {
            if ($gateway->provider === 'stripe') {
                // Mock Stripe test
                $connected = !empty($gateway->api_secret);
            } elseif ($gateway->provider === 'paypal') {
                // Mock PayPal test
                $connected = !empty($gateway->api_key) && !empty($gateway->api_secret);
            } elseif ($gateway->provider === 'khqr') {
                // Mock KHQR test
                $connected = !empty($gateway->api_key); // Account KHR
            } else {
                $connected = true; // default custom
            }
        } catch (\Exception $e) {
            $connected = false;
        }

        return [
            'connected' => $connected,
            'message' => $connected ? 'Connection test successful' : 'Connection test failed'
        ];
    }
}
