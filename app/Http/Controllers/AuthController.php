<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\LogoutRequest;
use App\Http\Resources\Admin\AuthResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * Unified login for admin and user.
     *
     * Returns a Sanctum token and authenticated user payload.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): AuthResource
    {
        $payload = $this->authService->login(
            $request->string('email')->value(),
            $request->string('password')->value(),
        );

        return new AuthResource($payload);
    }

    /**
     * Unified logout for admin and user.
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
