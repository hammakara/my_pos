<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\API\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);


Route::middleware(['auth:sanctum'])->group(function(){
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/logout', [AuthController::class, 'logout']);

    Route::middleware(['permission:view_category'])->get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::middleware(['permission:create_category'])->post('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'store']);
    Route::middleware(['permission:view_category'])->get('/categories/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);
    Route::middleware(['permission:update_category'])->put('/categories/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'update']);
    Route::middleware(['permission:delete_category'])->delete('/categories/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'destroy']);

    Route::middleware(['permission:view_product'])->get('/products', [ProductController::class, 'index']);
    Route::middleware(['permission:create_product'])->post('/products', [ProductController::class, 'store']);
    Route::middleware(['permission:view_product'])->get('/products/{product}', [ProductController::class, 'show']);
    Route::middleware(['permission:update_product'])->put('/products/{product}', [ProductController::class, 'update']);
    Route::middleware(['permission:delete_product'])->delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::apiResource('tables', \App\Http\Controllers\Api\CafeTableController::class);
    Route::patch('/tables/{id}/status', [\App\Http\Controllers\Api\CafeTableController::class, 'updateStatus']);

    Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders', function() {
        return \App\Http\Resources\OrderResource::collection(\App\Models\Order::with(['items.variant.product', 'table'])->latest()->get());
    });
    Route::patch('/orders/{id}/status', [\App\Http\Controllers\Api\OrderController::class, 'updateStatus']);

    Route::get('/reports/dashboard', [\App\Http\Controllers\Api\ReportController::class, 'dashboard']);

    Route::get('/settings', [\App\Http\Controllers\Api\SettingController::class, 'index']);
    Route::patch('/settings', [\App\Http\Controllers\Api\SettingController::class, 'update']);
});