<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // If user doesn't have a company, redirect to onboarding
        if (!$user->company_id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please create a company to continue.',
                    'data' => null,
                    'errors' => ['company' => ['User must be associated with a company']],
                    'meta' => [],
                ], 403);
            }

            // For web requests, redirect will be handled by frontend router
            abort(403, 'User must be associated with a company');
        }

        return $next($request);
    }
}
