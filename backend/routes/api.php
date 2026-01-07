<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SubjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Get current authenticated user
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');

        // Example: Routes protected by role middleware
        Route::middleware('role:admin')->group(function () {
            // Admin-only routes can go here
        });

        // Example: Routes protected by permission middleware
        Route::middleware('permission:manage users')->group(function () {
            // User management routes can go here
        });

        // Example: Posts resource with permission-based access
        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', [PostController::class, 'index'])->name('index');
            Route::get('/{id}', [PostController::class, 'show'])->name('show');

            Route::post('/', [PostController::class, 'store'])
                ->middleware('permission:create posts')
                ->name('store');

            Route::put('/{id}', [PostController::class, 'update'])
                ->middleware('permission:edit posts')
                ->name('update');

            Route::delete('/{id}', [PostController::class, 'destroy'])
                ->middleware('permission:delete posts')
                ->name('destroy');
        });

        // Alternative: Using role_or_permission middleware
        Route::middleware('role_or_permission:admin|manage users')->group(function () {
            // Routes accessible by admin role OR manage users permission
        });

        // Existing resource routes
        Route::apiResource('students', StudentController::class);
        Route::apiResource('subjects', SubjectController::class);
        Route::apiResource('enrollments', EnrollmentController::class);
    });
});
