<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user account
     *
     * @param RegisterRequest $request Validated registration data
     * @return JsonResponse JSON response with user data
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());
        return $this->createdResponse([
            'user' => new UserResource($result['user']),
        ], 'User registered successfully');
    }

    /**
     * Authenticate a user
     *
     * @param LoginRequest $request Validated login credentials
     * @return JsonResponse JSON response with user data
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->only('email', 'password'));
        return $this->successResponse([
            'user' => new UserResource($result['user']),
        ], 'Login successful');
    }

    /**
     * Logout the current user by revoking their access token
     *
     * @param Request $request Current HTTP request
     * @return JsonResponse JSON response confirming logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);
        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Get the currently authenticated user's profile
     *
     * @param Request $request Current HTTP request
     * @return JsonResponse JSON response with user data
     */
    public function user(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request);
        return $this->successResponse(new UserResource($user), 'User profile retrieved successfully');
    }
}
