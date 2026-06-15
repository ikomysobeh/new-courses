<?php

namespace App\Services\Department;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function getCards(): array
    {
        return [
            [
                'key' => 'total_departments',
                'title' => 'Total Departments',
                'value' => Department::query()->count(),
            ],
            [
                'key' => 'root_departments',
                'title' => 'Root Departments',
                'value' => Department::query()->whereNull('parent_id')->count(),
            ],
            [
                'key' => 'users_with_department',
                'title' => 'Users With Department',
                'value' => User::query()->whereNotNull('department_id')->count(),
            ],
        ];
    }

    public function getAll(): Collection
    {
        
        return Department::query()
            ->whereNull('parent_id')
            ->with($this->treeRelations())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            $slug = $this->generateUniqueSlug($data['name']);

            return Department::query()->create([
                'name' => $data['name'],
                'slug' => $slug,
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function update(Department $department, array $data): Department
    {
        return DB::transaction(function () use ($department, $data) {
            $newParentId = $data['parent_id'] ?? null;

            if ($newParentId !== null) {
                if ((int) $newParentId === (int) $department->id) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['A department cannot be its own parent.'],
                    ]);
                }

                if ($this->wouldCreateCycle($department, (int) $newParentId)) {
                    throw ValidationException::withMessages([
                        'parent_id' => ['This parent selection would create a circular hierarchy.'],
                    ]);
                }
            }

            $payload = [
                'name' => $data['name'],
                'parent_id' => $newParentId,
                'sort_order' => $data['sort_order'] ?? $department->sort_order,
            ];

            if ($data['name'] !== $department->name) {
                $payload['slug'] = $this->generateUniqueSlug($data['name'], $department->id);
            }

            $department->update($payload);

            return $department->fresh();
        });
    }

    public function delete(Department $department): void
    {
        if ($department->children()->exists()) {
            throw ValidationException::withMessages([
                'department' => ['Cannot delete a department that still has child departments.'],
            ]);
        }

        $department->delete();
    }

    private function wouldCreateCycle(Department $department, int $newParentId): bool
    {
        $currentId = $newParentId;

        while ($currentId !== 0) {
            if ($currentId === (int) $department->id) {
                return true;
            }

            $parentId = Department::query()
                ->whereKey($currentId)
                ->value('parent_id');

            if ($parentId === null) {
                return false;
            }

            $currentId = (int) $parentId;
        }

        return false;
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'department';

        $slug = $base;
        $counter = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $counter++;
            $slug = $base . '-' . $counter;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Department::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();
    }

    private function treeRelations(): array
    {
        return [
            'users.manager',
            'users.userLevelTier.userLevel',
            'children' => function ($query) {
                $query->with($this->treeRelations())
                    ->orderBy('sort_order')
                    ->orderBy('name');
            },
        ];
    }
}
