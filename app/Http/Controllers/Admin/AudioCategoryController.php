<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAudioCategoryRequest;
use App\Http\Requests\Admin\UpdateAudioCategoryRequest;
use App\Http\Resources\Audio\AudioCategoryResource;
use App\Models\AudioCategory;
use App\Services\Audio\AudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AudioCategoryController extends Controller
{
    public function __construct(private readonly AudioService $audioService) {}

    /**
     * List all audio categories.
     */
    public function getAll(): AnonymousResourceCollection
    {
        $categories = $this->audioService->getAllCategories();

        return AudioCategoryResource::collection($categories)
            ->additional([
                'cards' => $this->audioService->getCategoryCards(),
            ]);
    }

    /**
     * Create a new audio category.
     */
    public function create(StoreAudioCategoryRequest $request): JsonResponse
    {
        $category = $this->audioService->createCategory($request->validated());

        return (new AudioCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing audio category.
     */
    public function update(UpdateAudioCategoryRequest $request, int $id): AudioCategoryResource
    {
        $category = AudioCategory::query()->findOrFail($id);
        $category = $this->audioService->updateCategory($category, $request->validated());

        return new AudioCategoryResource($category);
    }

    /**
     * Delete an audio category.
     */
    public function delete(int $id): JsonResponse
    {
        $category = AudioCategory::query()->findOrFail($id);
        $this->audioService->deleteCategory($category);

        return response()->json([
            'message' => 'Audio category deleted successfully.',
        ]);
    }
}
