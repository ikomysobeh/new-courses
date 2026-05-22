<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Http\Resources\Admin\UserListResource;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * List all users
     *
     * Returns a paginated list of users. Supports filters: `department_id`, `user_level_tier_id`, `search` (name/email), `per_page`.
     */
    public function getAll(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['department_id', 'user_level_tier_id', 'search']);
        $perPage = (int) $request->query('per_page', 15);

        $users = $this->userService->getAll($filters, $perPage);

        return UserListResource::collection($users)
            ->additional([
                'cards' => $this->userService->getCards(),
            ]);
    }

    /**
     * Get a user by ID
     *
     * Returns the full details of a single user.
     */
    public function getById(int $id): JsonResponse
    {
        $user = User::with(['department', 'manager', 'userLevelTier.userLevel'])->findOrFail($id);

        return (new UserResource($user))->response();
    }

    /**
     * Create a user
     *
     * Creates a new user. Default role is `user`. Pass `role: admin` to create an admin.
     */
    public function create(UserStoreRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a user
     *
     * Update any user field including role (admin/user).
     */
    public function update(UserUpdateRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user = $this->userService->update($user, $request->validated());

        return (new UserResource($user))->response();
    }

    /**
     * Delete a user
     *
     * Soft-deletes the user. Fails if the user is a direct manager of other users.
     */
    public function delete(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $this->userService->delete($user);

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
