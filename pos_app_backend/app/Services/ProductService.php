<?php

namespace App\Services;

use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProductService extends BaseService
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAllProducts(int $perPage = 15)
    {
        return \App\Models\Product::with(['category', 'variants'])->paginate($perPage);
    }

    public function createProduct(array $data)
    {
        return DB::transaction(function () use ($data) {
            $product = $this->productRepository->create([
                'category_id' => $data['category_id'] ?? null,
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'status'      => $data['status'] ?? 'active',
                'user_id'     => auth()->id(),
            ]);

            if (isset($data['image'])) {
                $product->addMedia($data['image'])->toMediaCollection('product_images');
            }

            if (isset($data['variants']) && is_array($data['variants'])) {
                foreach ($data['variants'] as $variant) {
                    $product->variants()->create([
                        'size_name' => $variant['size_name'] ?? 'Regular',
                        'price'     => $variant['price'],
                        'stock_qty' => $variant['stock_qty'] ?? 0,
                    ]);
                }
            }

            return $product->load(['category', 'variants']);
        });
    }

    public function updateProduct(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->productRepository->find($id);
            if (!$product) return false;

            $product->update([
                'category_id' => $data['category_id'] ?? $product->category_id,
                'name'        => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'status'      => $data['status'] ?? $product->status,
            ]);

            if (isset($data['image'])) {
                $product->clearMediaCollection('product_images');
                $product->addMedia($data['image'])->toMediaCollection('product_images');
            }

            if (isset($data['variants']) && is_array($data['variants'])) {
                $incomingVariantIds = collect($data['variants'])
                    ->filter(fn($v) => isset($v['id']))
                    ->pluck('id')
                    ->toArray();
                
                // Attempt to delete variants not present in the request
                $variantsToDelete = $product->variants()->whereNotIn('id', $incomingVariantIds)->get();
                foreach ($variantsToDelete as $variant) {
                    try {
                        $variant->delete();
                    } catch (\Illuminate\Database\QueryException $e) {
                        \Log::warning("Skipped deletion of variant '{$variant->size_name}' (ID: {$variant->id}) due to existing orders.");
                        // Silently skip deletion for variants with existing orders
                    }
                }

                // Update or create new variants
                foreach ($data['variants'] as $variant) {
                    $attributes = ['size_name' => $variant['size_name'] ?? 'Regular'];
                    if (!empty($variant['id'])) {
                        $attributes['id'] = (int) $variant['id'];
                    }

                    $product->variants()->updateOrCreate(
                        $attributes,
                        [
                            'price'     => $variant['price'],
                            'stock_qty' => $variant['stock_qty'] ?? 0,
                        ]
                    );
                }
            }

            return $product->load(['category', 'variants']);
        });
    }

    public function deleteProduct(int $id)
    {
        return $this->productRepository->delete($id);
    }
}
