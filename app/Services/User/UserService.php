<?php

namespace App\Services\User;

use App\Models\User;
use App\Support\Filtering\FilterableQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class UserService
{
    use FilterableQuery;

    public function getCards(): array
    {
        return [
            [
                'key' => 'total_users',
                'title' => 'Total Users',
                'value' => User::query()->count(),
            ],
            [
                'key' => 'admin_users',
                'title' => 'Admin Users',
                'value' => User::query()->where('role', 'admin')->count(),
            ],
            [
                'key' => 'regular_users',
                'title' => 'Regular Users',
                'value' => User::query()->where('role', 'user')->count(),
            ],
            [
                'key' => 'users_with_manager',
                'title' => 'Users With Manager',
                'value' => User::query()->has('managers')->count(),
            ],
        ];
    }

    public function getAll(array $params = []): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['department', 'userLevelTier.userLevel', 'manager', 'managers']);

        return $this->applyFilters($query, $params, [
            'searchable'  => ['name', 'email'],
            'filters'     => [
                'department_id'      => 'exact',
                'user_level_tier_id' => 'exact',
                'role'               => 'exact',
            ],
            'dateColumn'  => 'created_at',
            'sortable'    => ['name', 'email', 'created_at'],
            'defaultSort' => ['name', 'asc'],
            'perPage'     => 15,
        ]);
    }

    public function create(array $data): User
    {
        $managerIds = $this->resolveManagerIds($data);

        $user = User::query()->create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $data['password'],
            'role'               => $data['role'] ?? 'user',
            'department_id'      => $data['department_id'] ?? null,
            // report_to mirrors the primary (first) manager for backward compatibility.
            'report_to'          => $managerIds[0] ?? null,
            'user_level_tier_id' => $data['user_level_tier_id'] ?? null,
        ]);

        $this->assertNotSelfManaged($user, $managerIds);
        $user->managers()->sync($managerIds);

        $user->load(['department', 'userLevelTier.userLevel', 'manager', 'managers']);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $payload = array_filter([
            'name'               => $data['name'] ?? null,
            'email'              => $data['email'] ?? null,
            'role'               => $data['role'] ?? null,
            'department_id'      => array_key_exists('department_id', $data) ? $data['department_id'] : null,
            'user_level_tier_id' => array_key_exists('user_level_tier_id', $data) ? $data['user_level_tier_id'] : null,
        ], fn ($v) => $v !== null);

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        // Only touch managers when the request actually sent them.
        $managersProvided = array_key_exists('manager_ids', $data) || array_key_exists('report_to', $data);

        if ($managersProvided) {
            $managerIds = $this->resolveManagerIds($data);
            $this->assertNotSelfManaged($user, $managerIds);
            $payload['report_to'] = $managerIds[0] ?? null;
        }

        $user->update($payload);

        if ($managersProvided) {
            $user->managers()->sync($managerIds);
        }

        $user->load(['department', 'userLevelTier.userLevel', 'manager', 'managers']);

        return $user;
    }

    /**
     * Normalize incoming manager input into a de-duplicated list of at most 2 ids.
     * Accepts either `manager_ids` (array) or the legacy single `report_to`.
     */
    private function resolveManagerIds(array $data): array
    {
        $ids = [];

        if (array_key_exists('manager_ids', $data) && is_array($data['manager_ids'])) {
            $ids = $data['manager_ids'];
        } elseif (array_key_exists('report_to', $data) && $data['report_to'] !== null) {
            $ids = [$data['report_to']];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (count($ids) > 2) {
            throw ValidationException::withMessages([
                'manager_ids' => ['A user can report to at most 2 managers.'],
            ]);
        }

        return $ids;
    }

    private function assertNotSelfManaged(User $user, array $managerIds): void
    {
        if (in_array((int) $user->id, $managerIds, true)) {
            throw ValidationException::withMessages([
                'manager_ids' => ['A user cannot report to themselves.'],
            ]);
        }
    }

    public function delete(User $user): void
    {
        if ($user->isDirectManager()) {
            throw ValidationException::withMessages([
                'id' => ['Cannot delete a user who manages other users. Reassign their subordinates first.'],
            ]);
        }

        $user->delete();
    }
}
