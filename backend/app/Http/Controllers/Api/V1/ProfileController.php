<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\UserResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileController extends BaseApiController
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get the authenticated user's profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        return $this->successResponse(
            new UserResource($user),
            'User profile retrieved successfully'
        );
    }

    /**
     * Upload user avatar
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();
        $user = $this->profileService->uploadAvatar($user, $request->file('avatar'));

        return $this->successResponse(
            new UserResource($user),
            'Avatar uploaded successfully'
        );
    }
}
