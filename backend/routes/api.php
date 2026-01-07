<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SubjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    // Public authentication routes
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Authentication routes (protected)
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');

        // Example: Routes protected by role middleware
        // Only users with 'admin' role can access
        Route::middleware('role:admin')->group(function () {
            // Admin-only routes can go here
            // Example: Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        });

        // Example: Routes protected by permission middleware
        // Only users with 'manage users' permission can access
        Route::middleware('permission:manage users')->group(function () {
            // User management routes can go here
            // Example: Route::apiResource('users', UserController::class);
        });

        // Example: Posts resource with permission-based access
        // Using permission middleware on specific routes
        Route::prefix('posts')->name('posts.')->group(function () {
            Route::get('/', [PostController::class, 'index'])->name('index');
            Route::get('/{id}', [PostController::class, 'show'])->name('show');

            // Require 'create posts' permission
            Route::post('/', [PostController::class, 'store'])
                ->middleware('permission:create posts')
                ->name('store');

            // Require 'edit posts' permission
            Route::put('/{id}', [PostController::class, 'update'])
                ->middleware('permission:edit posts')
                ->name('update');

            // Require 'delete posts' permission
            Route::delete('/{id}', [PostController::class, 'destroy'])
                ->middleware('permission:delete posts')
                ->name('destroy');
        });

        // Alternative: Using role_or_permission middleware
        // Allows access if user has either 'admin' role OR 'manage users' permission
        Route::middleware('role_or_permission:admin|manage users')->group(function () {
            // Routes accessible by admin role OR manage users permission
        });

        // Existing resource routes
        // Note: Authorization (admin/student access) is handled by policies
        Route::apiResource('students', StudentController::class);
        Route::apiResource('subjects', SubjectController::class);
        Route::apiResource('enrollments', EnrollmentController::class);
    });
});
