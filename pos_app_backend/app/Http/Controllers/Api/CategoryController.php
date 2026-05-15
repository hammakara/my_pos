<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        $categories = $this->categoryService->getAllCategories();
        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:active,inactive',
            'image'       => 'nullable|image|max:2048',
        ]);

        $category = $this->categoryService->createCategory($data);

        return new CategoryResource($category);
    }

    public function show(int $id)
    {
        $category = $this->categoryService->getAllCategories()->find($id);
        if (!$category) return response()->json(['message' => 'Not found'], 404);
        
        return new CategoryResource($category);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:active,inactive',
            'image'       => 'nullable|image|max:2048',
        ]);

        $category = $this->categoryService->updateCategory($id, $data);
        if (!$category) return response()->json(['message' => 'Not found'], 404);

        return new CategoryResource($category);
    }

    public function destroy(int $id)
    {
        $this->categoryService->deleteCategory($id);
        return response()->json(null, 204);
    }
}
