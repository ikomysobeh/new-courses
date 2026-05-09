<?php

namespace App\Services\Video;

use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class VideoCategoryService
{
    public function getAllCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = VideoCategory::query()->orderBy('sort_order')->orderBy('name');

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    public function getCategoryById(int $id): VideoCategory
    {
        return VideoCategory::query()->findOrFail($id);
    }

    public function createCategory(array $data): VideoCategory
    {
        $data['slug'] = $this->generateUniqueSlug($data['name']);

        return VideoCategory::query()->create($data);
    }

    public function updateCategory(int $id, array $data): VideoCategory
    {
        $category = VideoCategory::query()->findOrFail($id);

        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
        }

        $category->update($data);

        return $category->fresh();
    }

    public function deleteCategory(int $id): void
    {
        $category = VideoCategory::query()->findOrFail($id);

        abort_if(
            $category->videos()->exists(),
            422,
            'Cannot delete a category that has videos.'
        );

        $category->delete();
    }

    public function getCategoryCards(): array
    {
        return [
            [
                'key'   => 'total_categories',
                'title' => 'Total Categories',
                'value' => VideoCategory::query()->count(),
            ],
            [
                'key'   => 'total_videos',
                'title' => 'Total Videos',
                'value' => Video::query()->count(),
            ],
        ];
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (
            VideoCategory::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
