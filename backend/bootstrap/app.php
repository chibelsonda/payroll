<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                    'data' => null,
                    'errors' => ['auth' => ['Unauthenticated']],
                    'meta' => [],
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                    'data' => null,
                    'errors' => ['auth' => ['This action is unauthorized']],
                    'meta' => [],
                ], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'data' => null,
                    'errors' => $e->errors(),
                    'meta' => [],
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'data' => null,
                    'errors' => ['resource' => ['The requested resource could not be found']],
                    'meta' => [],
                ], 404);
            }
        });

        // Handle InvalidCredentialsException before generic HttpException
        $exceptions->render(function (\App\Exceptions\InvalidCredentialsException $e, $request) {
            if ($request->is('api/*') || $request->is('login') || $request->is('register')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Invalid credentials',
                    'data' => null,
                    'errors' => ['credentials' => [$e->getMessage() ?: 'Invalid credentials']],
                    'meta' => [],
                ], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'HTTP error occurred.',
                    'data' => null,
                    'errors' => ['http' => [$e->getMessage() ?: 'HTTP error']],
                    'meta' => [],
                ], $e->getStatusCode());
            }
        });

        $exceptions->render(function (\Exception $e, $request) {
            if ($request->is('api/*')) {
                // Log the error for debugging
                \Illuminate\Support\Facades\Log::error('API Error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request' => $request->all(),
                    'trace' => $e->getTraceAsString()
                ]);

                $message = app()->environment('local') ? $e->getMessage() : 'An error occurred while processing your request.';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'errors' => ['server' => [$message]],
                    'meta' => [],
                ], 500);
            }
        });
    })->create();
