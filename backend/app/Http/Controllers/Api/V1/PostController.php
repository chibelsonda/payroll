<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends BaseApiController
{
    /**
     * Display a listing of posts.
     * Accessible by all authenticated users.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Example: No permission check needed for listing
        return $this->successResponse(
            ['posts' => []],
            'Posts retrieved successfully'
        );
    }

    /**
     * Store a newly created post.
     * Requires 'create posts' permission.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Method 1: Using middleware (recommended - see routes/api.php)
        // Method 2: Using controller check (alternative approach)
        if (!$request->user()->hasPermissionTo('create posts')) {
            return $this->forbiddenResponse('You do not have permission to create posts');
        }

        // Your post creation logic here
        return $this->createdResponse(
            ['post' => ['id' => 1, 'title' => 'Example Post']],
            'Post created successfully'
        );
    }

    /**
     * Display the specified post.
     * Accessible by all authenticated users.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        return $this->successResponse(
            ['post' => ['id' => $id]],
            'Post retrieved successfully'
        );
    }

    /**
     * Update the specified post.
     * Requires 'edit posts' permission.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Check permission using Spatie Permission
        if (!$request->user()->can('edit posts')) {
            return $this->forbiddenResponse('You do not have permission to edit posts');
        }

        // Your post update logic here
        return $this->successResponse(
            ['post' => ['id' => $id, 'updated' => true]],
            'Post updated successfully'
        );
    }

    /**
     * Remove the specified post.
     * Requires 'delete posts' permission.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Check permission using hasPermissionTo
        if (!auth()->user()->hasPermissionTo('delete posts')) {
            return $this->forbiddenResponse('You do not have permission to delete posts');
        }

        // Your post deletion logic here
        return $this->noContentResponse('Post deleted successfully');
    }
}
