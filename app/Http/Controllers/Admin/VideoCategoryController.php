<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVideoCategoryRequest;
use App\Http\Requests\Admin\UpdateVideoCategoryRequest;
use App\Http\Resources\Video\VideoCategoryResource;
use App\Services\Video\VideoCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VideoCategoryController extends Controller
{
    public function __construct(private readonly VideoCategoryService $categoryService) {}

    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search']);
        $perPage = (int) $request->query('per_page', 15);

        $categories = $this->categoryService->getAllCategories($filters, $perPage);

        return VideoCategoryResource::collection($categories)
            ->additional([
                'cards' => $this->categoryService->getCategoryCards(),
            ]);
    }

    public function create(StoreVideoCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());

        return (new VideoCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function getById(int $id): VideoCategoryResource
    {
        $category = $this->categoryService->getCategoryById($id);

        return new VideoCategoryResource($category);
    }

    public function update(UpdateVideoCategoryRequest $request, int $id): VideoCategoryResource
    {
        $category = $this->categoryService->updateCategory($id, $request->validated());

        return new VideoCategoryResource($category);
    }

    public function delete(int $id): JsonResponse
    {
        $this->categoryService->deleteCategory($id);

        return response()->json(['message' => 'Video category deleted successfully.']);
    }
}
