<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $products = $this->productService->getAllProducts($perPage);
        return ProductResource::collection($products);
    }

    public function store(Request $request)
    {
        \Log::info('Product Store Request', [
            'has_image' => $request->hasFile('image'),
            'all_files' => array_keys($request->allFiles()),
            'all_inputs' => array_keys($request->all()),
            'content_type' => $request->header('Content-Type'),
        ]);

        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:active,inactive',
            'image'       => 'nullable|image|max:2048',
            'variants'    => 'required|array|min:1',
            'variants.*.size_name' => 'required|string',
            'variants.*.price'     => 'required|numeric|min:0',
            'variants.*.stock_qty' => 'nullable|integer|min:0',
        ]);

        \Log::info('Product Store Validated Data', ['has_image_in_data' => isset($data['image'])]);

        $product = $this->productService->createProduct($data);

        return new ProductResource($product);
    }

    public function show(int $id)
    {
        $product = $this->productService->getAllProducts()->find($id);
        if (!$product) return response()->json(['message' => 'Not found'], 404);

        return new ProductResource($product);
    }

    public function update(Request $request, int $id)
    {
        try {
            \Log::info('Product Update Request', [
                'product_id' => $id,
                'user_id' => auth()->id(),
                'has_image' => $request->hasFile('image'),
                'all_inputs' => array_keys($request->all()),
                'content_type' => $request->header('Content-Type'),
            ]);

            $data = $request->validate([
                'category_id' => 'sometimes|required|exists:categories,id',
                'name'        => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'status'      => 'nullable|in:active,inactive',
                'image'       => 'nullable|image|max:2048',
                'variants'    => 'sometimes|required|array|min:1',
                'variants.*.size_name' => 'required|string',
                'variants.*.price'     => 'required|numeric|min:0',
                'variants.*.stock_qty' => 'nullable|integer|min:0',
            ]);

            \Log::info('Product Update Validated', ['has_image_in_data' => isset($data['image'])]);

            $product = $this->productService->updateProduct($id, $data);
            
            if (!$product) {
                \Log::error('Product not found', ['product_id' => $id]);
                return response()->json(['message' => 'Product not found'], 404);
            }

            \Log::info('Product Updated Successfully', ['product_id' => $id]);
            return new ProductResource($product);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Product Update Validation Failed', [
                'product_id' => $id,
                'errors' => $e->errors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Product Update Failed', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            \Log::info('Product Delete Request', [
                'product_id' => $id,
                'user_id' => auth()->id(),
            ]);

            $result = $this->productService->deleteProduct($id);
            
            if (!$result) {
                \Log::error('Product not found for deletion', ['product_id' => $id]);
                return response()->json(['message' => 'Product not found'], 404);
            }

            \Log::info('Product Deleted Successfully', ['product_id' => $id]);
            return response()->json(null, 204);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Product Delete Constraint Violation', ['product_id' => $id, 'error' => $e->getMessage()]);
            if ($e->getCode() == '23000') {
                return response()->json([
                    'message' => 'Cannot delete product because it has associated orders. Please set its status to Inactive instead.'
                ], 400);
            }
            return response()->json(['message' => 'Database error occurred', 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            \Log::error('Product Delete Failed', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
