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
            'variants.*.barcode'   => 'nullable|string|unique:product_variants,barcode',
        ]);

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
            'variants.*.barcode'   => 'nullable|string',
        ]);

        $product = $this->productService->updateProduct($id, $data);
        if (!$product) return response()->json(['message' => 'Not found'], 404);

        return new ProductResource($product);
    }

    public function destroy(int $id)
    {
        $this->productService->deleteProduct($id);
        return response()->json(null, 204);
    }
}
