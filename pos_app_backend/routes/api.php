<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CafeTableController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\PaymentGatewayController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SelfServiceController;

// ─── Public ──────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ─── Public Customer Self-Service (QR Table Ordering) ───────────────────────
Route::prefix('public')->group(function () {
    Route::get('/menu',         [SelfServiceController::class, 'getMenu']);
    Route::get('/tables/{id}',  [SelfServiceController::class, 'getTable']);
    Route::post('/orders',      [SelfServiceController::class, 'placeOrder']);
    Route::get('/orders/{id}',  [SelfServiceController::class, 'trackOrder']);
});


// ─── Authenticated ───────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/logout',  [AuthController::class, 'logout']);

    // Categories
    Route::middleware(['permission:view_category'])->get('/categories', [CategoryController::class, 'index']);
    Route::middleware(['permission:create_category'])->post('/categories', [CategoryController::class, 'store']);
    Route::middleware(['permission:view_category'])->get('/categories/{category}', [CategoryController::class, 'show']);
    Route::middleware(['permission:update_category'])->put('/categories/{category}', [CategoryController::class, 'update']);
    Route::middleware(['permission:delete_category'])->delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Products
    Route::middleware(['permission:view_product'])->get('/products', [ProductController::class, 'index']);
    Route::middleware(['permission:create_product'])->post('/products', [ProductController::class, 'store']);
    Route::middleware(['permission:view_product'])->get('/products/{product}', [ProductController::class, 'show']);
    Route::middleware(['permission:update_product'])->put('/products/{product}', [ProductController::class, 'update']);
    Route::middleware(['permission:delete_product'])->delete('/products/{product}', [ProductController::class, 'destroy']);

    // Tables
    Route::apiResource('tables', CafeTableController::class);
    Route::patch('/tables/{id}/status', [CafeTableController::class, 'updateStatus']);

    // Orders
    Route::get('/orders',                    [OrderController::class, 'index']);
    Route::post('/orders',                   [OrderController::class, 'store']);
    Route::get('/orders/{id}',               [OrderController::class, 'show']);
    Route::patch('/orders/{id}/status',      [OrderController::class, 'updateStatus']);
    Route::patch('/orders/{id}/items',       [OrderController::class, 'updateItems']);
    Route::patch('/orders/{id}/payment',     [OrderController::class, 'updatePayment']);
    Route::post('/orders/{id}/cancel',       [OrderController::class, 'cancel']);

    // Reports
    Route::get('/reports/dashboard',  [ReportController::class, 'dashboard']);
    Route::get('/reports/sales',      [ReportController::class, 'sales']);
    Route::get('/reports/products',   [ReportController::class, 'products']);
    Route::get('/reports/inventory',  [ReportController::class, 'inventory']);

    // Settings
    Route::get('/settings',   [SettingController::class, 'index']);
    Route::patch('/settings', [SettingController::class, 'update']);

    // ─── User & Role Management ───────────────────────────────────────────────
    Route::prefix('users')->group(function () {
        Route::get('/',        [RoleController::class, 'users']);
        Route::post('/',       [RoleController::class, 'storeUser']);
        Route::get('/{id}',    [RoleController::class, 'showUser']);
        Route::put('/{id}',    [RoleController::class, 'updateUser']);
        Route::delete('/{id}', [RoleController::class, 'destroyUser']);
    });

    Route::prefix('roles')->group(function () {
        Route::get('/',        [RoleController::class, 'index']);
        Route::post('/',       [RoleController::class, 'store']);
        Route::get('/{id}',    [RoleController::class, 'show']);
        Route::put('/{id}',    [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });

    Route::get('/permissions', [RoleController::class, 'permissions']);

    // ─── Suppliers ────────────────────────────────────────────────────────────
    Route::apiResource('suppliers', SupplierController::class);

    // ─── Purchase Orders ──────────────────────────────────────────────────────
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive']);

    // ─── Stock ────────────────────────────────────────────────────────────────
    Route::get('/stock/movements',  [StockController::class, 'movements']);
    Route::post('/stock/adjust',    [StockController::class, 'adjust']);
    Route::get('/stock/low-stock',  [StockController::class, 'lowStock']);

    // ─── Customers ────────────────────────────────────────────────────────────
    Route::apiResource('customers', CustomerController::class);

    // ─── Budget Management ────────────────────────────────────────────────────
    Route::prefix('budgets')->group(function () {
        Route::get('/',              [BudgetController::class, 'index']);
        Route::post('/',             [BudgetController::class, 'store']);
        Route::get('/{budget}',      [BudgetController::class, 'show']);
        Route::put('/{budget}',      [BudgetController::class, 'update']);
        Route::delete('/{budget}',   [BudgetController::class, 'destroy']);
        Route::get('/{budget}/statistics', [BudgetController::class, 'getStatistics']);
    });

    // ─── Loyalty Programs ──────────────────────────────────────────────────────
    Route::prefix('loyalty')->group(function () {
        Route::get('/',                              [LoyaltyController::class, 'index']);
        Route::post('/',                             [LoyaltyController::class, 'store']);
        Route::get('/{program}',                     [LoyaltyController::class, 'show']);
        Route::put('/{program}',                     [LoyaltyController::class, 'update']);
        Route::delete('/{program}',                  [LoyaltyController::class, 'destroy']);
        Route::post('/enroll',                       [LoyaltyController::class, 'enrollCustomer']);
        Route::get('/customer/{customerId}',          [LoyaltyController::class, 'getCustomerPoints']);
        Route::post('/add-points',                    [LoyaltyController::class, 'addPoints']);
        Route::post('/redeem-points',                 [LoyaltyController::class, 'redeemPoints']);
    });

    // ─── Payment Gateways ─────────────────────────────────────────────────────
    Route::prefix('payment-gateways')->group(function () {
        Route::get('/',                              [PaymentGatewayController::class, 'index']);
        Route::post('/',                             [PaymentGatewayController::class, 'store']);
        Route::get('/{gateway}',                     [PaymentGatewayController::class, 'show']);
        Route::put('/{gateway}',                     [PaymentGatewayController::class, 'update']);
        Route::delete('/{gateway}',                  [PaymentGatewayController::class, 'destroy']);
        Route::post('/{gateway}/test',               [PaymentGatewayController::class, 'testConnection']);
    });
});
