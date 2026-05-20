<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Test endpoint to verify image upload works
Route::post('/test-upload', function (Request $request) {
    return response()->json([
        'has_image' => $request->hasFile('image'),
        'image_info' => $request->hasFile('image') ? [
            'name' => $request->file('image')->getClientOriginalName(),
            'size' => $request->file('image')->getSize(),
            'mime' => $request->file('image')->getMimeType(),
        ] : null,
        'all_files' => array_keys($request->allFiles()),
        'all_inputs' => array_keys($request->all()),
        'content_type' => $request->header('Content-Type'),
    ]);
});

// Test product update
Route::post('/test-product-update/{id}', function (Request $request, $id) {
    try {
        $product = \App\Models\Product::findOrFail($id);
        
        \Log::info('Test Product Update', [
            'product_id' => $id,
            'has_image' => $request->hasFile('image'),
            'all_inputs' => array_keys($request->all()),
            'variants_count' => $product->variants()->count(),
        ]);
        
        // Try to delete variants
        $deleted = $product->variants()->delete();
        
        return response()->json([
            'success' => true,
            'product_id' => $id,
            'variants_deleted' => $deleted,
            'message' => 'Test successful'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
