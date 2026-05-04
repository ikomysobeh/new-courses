<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LogoutRequest;
use App\Http\Resources\Admin\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * User logout
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(): UserResource
    {
        return new UserResource(request()->user());
    }
}
