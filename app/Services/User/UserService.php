<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['department', 'userLevelTier.userLevel', 'manager'])
            ->orderBy('name');

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['user_level_tier_id'])) {
            $query->where('user_level_tier_id', $filters['user_level_tier_id']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): User
    {
        $user = User::query()->create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $data['password'],
            'role'               => $data['role'] ?? 'user',
            'department_id'      => $data['department_id'] ?? null,
            'report_to'          => $data['report_to'] ?? null,
            'user_level_tier_id' => $data['user_level_tier_id'] ?? null,
        ]);

        $user->load(['department', 'userLevelTier.userLevel', 'manager']);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $payload = array_filter([
            'name'               => $data['name'] ?? null,
            'email'              => $data['email'] ?? null,
            'role'               => $data['role'] ?? null,
            'department_id'      => array_key_exists('department_id', $data) ? $data['department_id'] : null,
            'report_to'          => array_key_exists('report_to', $data) ? $data['report_to'] : null,
            'user_level_tier_id' => array_key_exists('user_level_tier_id', $data) ? $data['user_level_tier_id'] : null,
        ], fn ($v) => $v !== null);

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        if (isset($data['report_to']) && (int) $data['report_to'] === (int) $user->id) {
            throw ValidationException::withMessages([
                'report_to' => ['A user cannot report to themselves.'],
            ]);
        }

        $user->update($payload);
        $user->load(['department', 'userLevelTier.userLevel', 'manager']);

        return $user;
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
