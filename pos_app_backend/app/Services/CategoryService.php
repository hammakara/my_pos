<?php

namespace App\Services;

use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Support\Str;

class CategoryService extends BaseService
{
    protected $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getAllCategories()
    {
        return $this->categoryRepository->all();
    }

    public function createCategory(array $data)
    {
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        $category = $this->categoryRepository->create($data);

        if (isset($data['image'])) {
            $category->addMedia($data['image'])->toMediaCollection('category_images');
        }

        return $category;
    }

    public function updateCategory(int $id, array $data)
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category = $this->categoryRepository->find($id);
        if (!$category) return false;

        $category->update($data);

        if (isset($data['image'])) {
            $category->clearMediaCollection('category_images');
            $category->addMedia($data['image'])->toMediaCollection('category_images');
        }

        return $category;
    }

    public function deleteCategory(int $id)
    {
        return $this->categoryRepository->delete($id);
    }
}
