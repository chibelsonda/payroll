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
            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
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

        // Handle database query exceptions (prevents exposing SQL errors to UI)
        // This handler MUST come before PDOException handler since QueryException extends PDOException
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            // Handle API routes, JSON requests (including register/login), and AJAX requests
            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                // Always log the full error details for debugging (never expose to UI)
                \Illuminate\Support\Facades\Log::error('Database Query Error', [
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'request_url' => $request->fullUrl(),
                    'request_method' => $request->method(),
                    'request_data' => $request->all(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Always return user-friendly message (never expose SQL details)
                $message = 'A database error occurred while processing your request.';

                // Provide more specific messages for common database errors based on error content
                $errorMessage = $e->getMessage();

                if (str_contains($errorMessage, 'violates not-null constraint') || str_contains($errorMessage, 'null value in column')) {
                        $message = 'Required information is missing. Please check your input and try again.';
                } elseif (str_contains($errorMessage, 'violates unique constraint') ||
                          str_contains($errorMessage, 'duplicate key') ||
                          str_contains($errorMessage, 'already exists')) {
                    $message = 'This record already exists. Please use a different value.';
                } elseif (str_contains($errorMessage, 'violates foreign key constraint') ||
                          str_contains($errorMessage, 'foreign key constraint')) {
                    $message = 'Invalid reference. The related record does not exist or cannot be used.';
                } elseif (str_contains($errorMessage, 'connection') ||
                          str_contains($errorMessage, 'timeout') ||
                          str_contains($errorMessage, 'could not connect')) {
                    $message = 'Unable to connect to the database. Please try again later.';
                } elseif (str_contains($errorMessage, 'syntax error')) {
                    $message = 'An error occurred while processing your request. Please contact support if this persists.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'errors' => ['database' => [$message]],
                    'meta' => [],
                ], 500);
            }
        });

        // Handle PDO exceptions (catches other database-level errors)
        $exceptions->render(function (\PDOException $e, $request) {
            // Skip if it's a QueryException (already handled above)
            if ($e instanceof \Illuminate\Database\QueryException) {
                return null;
            }

            // Handle API routes, JSON requests (including register/login), and AJAX requests
            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                // Always log the full error details
                \Illuminate\Support\Facades\Log::error('PDO Error', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'exception' => get_class($e),
                    'request_url' => $request->fullUrl(),
                    'request_method' => $request->method(),
                    'request_data' => $request->all(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Always return user-friendly message
                $message = 'A database error occurred while processing your request.';

                if (str_contains($e->getMessage(), 'SQLSTATE')) {
                    $message = 'Unable to process your request. Please verify your input and try again.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'errors' => ['database' => [$message]],
                    'meta' => [],
                ], 500);
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

        // Handle all other exceptions (catch-all handler - must be last)
        $exceptions->render(function (\Throwable $e, $request) {
            // Skip database exceptions (already handled above)
            if ($e instanceof \Illuminate\Database\QueryException || $e instanceof \PDOException) {
                return null;
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                // Log the full error for debugging (never expose to UI)
                \Illuminate\Support\Facades\Log::error('API Error', [
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'request_url' => $request->fullUrl(),
                    'request_method' => $request->method(),
                    'request_data' => $request->all(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Always return user-friendly message (never expose technical details to UI)
                // Full error details are logged above for debugging
                $message = 'An error occurred while processing your request. Please try again later.';

                // Only in local/debug mode, provide slightly more context (but still sanitized)
                if (config('app.debug') && config('app.env') === 'local') {
                    // In local debug mode, you can optionally show the error class name for developers
                    // but still hide sensitive information like file paths, SQL queries, etc.
                    $message = 'An error occurred: ' . class_basename($e) . '. Check logs for details.';
                }

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
