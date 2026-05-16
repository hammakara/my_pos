<?php

/**
 * API Testing Script for POS Application
 * Run with: php test_api.php
 */

$baseUrl = 'http://pos_app_backend.test/api';
$token = null;
$errors = [];
$successes = [];

function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

function testEndpoint($name, $method, $endpoint, $data = null, $expectedCode = 200) {
    global $baseUrl, $token, $errors, $successes;
    
    echo "\n🧪 Testing: $name\n";
    echo "   Method: $method $endpoint\n";
    
    $response = makeRequest($method, $baseUrl . $endpoint, $data, $token);
    
    if ($response['code'] === $expectedCode) {
        echo "   ✅ PASSED (HTTP {$response['code']})\n";
        $successes[] = $name;
        return $response;
    } else {
        echo "   ❌ FAILED (Expected HTTP $expectedCode, got {$response['code']})\n";
        echo "   Response: " . substr($response['raw'], 0, 200) . "...\n";
        $errors[] = [
            'test' => $name,
            'expected' => $expectedCode,
            'actual' => $response['code'],
            'response' => $response['body']
        ];
        return $response;
    }
}

echo "═══════════════════════════════════════════════════════\n";
echo "  POS API Testing Suite\n";
echo "═══════════════════════════════════════════════════════\n";

// Test 1: Register
echo "\n📋 AUTH ENDPOINTS\n";
echo "─────────────────────────────────────────────────────\n";

$registerData = [
    'name' => 'Test User ' . time(),
    'email' => 'test' . time() . '@example.com',
    'password' => 'password123'
];
$response = testEndpoint('Register User', 'POST', '/register', $registerData, 201);

// Test 2: Login
$loginData = [
    'email' => 'admin@example.com',
    'password' => '12341234'
];
$response = testEndpoint('Login', 'POST', '/login', $loginData, 200);
if (isset($response['body']['token'])) {
    $token = $response['body']['token'];
    echo "   🔑 Token obtained\n";
}

// Test 3: Profile
$response = testEndpoint('Get Profile', 'GET', '/profile', null, 200);

// Test 4: Categories
echo "\n📋 CATEGORY ENDPOINTS\n";
echo "─────────────────────────────────────────────────────\n";

$response = testEndpoint('List Categories', 'GET', '/categories', null, 200);

$categoryData = [
    'name' => 'Test Category ' . time(),
    'description' => 'Test description',
    'status' => 'active'
];
$response = testEndpoint('Create Category', 'POST', '/categories', $categoryData, 200);
$categoryId = $response['body']['data']['id'] ?? null;

if ($categoryId) {
    $response = testEndpoint('Get Category', 'GET', "/categories/$categoryId", null, 200);
    
    $updateData = ['name' => 'Updated Category ' . time()];
    $response = testEndpoint('Update Category', 'PUT', "/categories/$categoryId", $updateData, 200);
}

// Test 5: Products
echo "\n📋 PRODUCT ENDPOINTS\n";
echo "─────────────────────────────────────────────────────\n";

$response = testEndpoint('List Products', 'GET', '/products', null, 200);

if ($categoryId) {
    $productData = [
        'category_id' => $categoryId,
        'name' => 'Test Product ' . time(),
        'description' => 'Test product description',
        'status' => 'active',
        'variants' => [
            [
                'size_name' => 'Small',
                'price' => 10.50,
                'stock_qty' => 100
            ],
            [
                'size_name' => 'Large',
                'price' => 15.00,
                'stock_qty' => 50
            ]
        ]
    ];
    $response = testEndpoint('Create Product', 'POST', '/products', $productData, 200);
    $productId = $response['body']['data']['id'] ?? null;
    
    if ($productId) {
        $response = testEndpoint('Get Product', 'GET', "/products/$productId", null, 200);
        
        $updateProductData = [
            'name' => 'Updated Product ' . time(),
            'variants' => [
                [
                    'size_name' => 'Medium',
                    'price' => 12.00,
                    'stock_qty' => 75
                ]
            ]
        ];
        $response = testEndpoint('Update Product', 'PUT', "/products/$productId", $updateProductData, 200);
    }
}

// Test 6: Tables
echo "\n📋 TABLE ENDPOINTS\n";
echo "─────────────────────────────────────────────────────\n";

$response = testEndpoint('List Tables', 'GET', '/tables', null, 200);

$tableData = [
    'number' => 'T' . time(),
    'capacity' => 4
];
$response = testEndpoint('Create Table', 'POST', '/tables', $tableData, 200);
$tableId = $response['body']['data']['id'] ?? null;

if ($tableId) {
    $statusData = ['status' => 'occupied'];
    $response = testEndpoint('Update Table Status', 'PATCH', "/tables/$tableId/status", $statusData, 200);
}

// Test 7: Orders
echo "\n📋 ORDER ENDPOINTS\n";
echo "─────────────────────────────────────────────────────\n";

$response = testEndpoint('List Orders', 'GET', '/orders', null, 200);

// Get a product variant for order
$productsResponse = makeRequest('GET', $baseUrl . '/products', null, $token);
$variantId = null;
if (isset($productsResponse['body']['data'][0]['variants'][0]['id'])) {
    $variantId = $productsResponse['body']['data'][0]['variants'][0]['id'];
    $variantPrice = $productsResponse['body']['data'][0]['variants'][0]['price'];
}

if ($variantId && $tableId) {
    $orderData = [
        'table_id' => $tableId,
        'type' => 'dine_in',
        'items' => [
            [
                'product_variant_id' => $variantId,
                'quantity' => 2,
                'unit_price' => $variantPrice,
                'subtotal' => $variantPrice * 2
            ]
        ]
    ];
    $response = testEndpoint('Create Order', 'POST', '/orders', $orderData, 200);
    $orderId = $response['body']['data']['id'] ?? null;
    
    if ($orderId) {
        $statusData = ['status' => 'cooking'];
        $response = testEndpoint('Update Order Status', 'PATCH', "/orders/$orderId/status", $statusData, 200);
    }
}

// Test 8: Reports
echo "\n📋 REPORT ENDPOINTS\n";
echo "─────────────────────────────────────────────────────\n";

$response = testEndpoint('Dashboard Report', 'GET', '/reports/dashboard', null, 200);

// Test 9: Settings
echo "\n📋 SETTING ENDPOINTS\n";
echo "─────────────────────────────────────────────────────\n";

$response = testEndpoint('Get Settings', 'GET', '/settings', null, 200);

$settingsData = [
    'cafe_name' => 'Test Cafe',
    'currency' => 'USD',
    'tax_rate' => 10
];
$response = testEndpoint('Update Settings', 'PATCH', '/settings', $settingsData, 200);

// Test 10: Logout
echo "\n📋 LOGOUT\n";
echo "─────────────────────────────────────────────────────\n";

$response = testEndpoint('Logout', 'GET', '/logout', null, 200);

// Summary
echo "\n═══════════════════════════════════════════════════════\n";
echo "  TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════\n";
echo "✅ Passed: " . count($successes) . "\n";
echo "❌ Failed: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\n🐛 ERRORS FOUND:\n";
    echo "─────────────────────────────────────────────────────\n";
    foreach ($errors as $error) {
        echo "\n❌ {$error['test']}\n";
        echo "   Expected: HTTP {$error['expected']}\n";
        echo "   Actual: HTTP {$error['actual']}\n";
        if (isset($error['response']['message'])) {
            echo "   Message: {$error['response']['message']}\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════\n";
