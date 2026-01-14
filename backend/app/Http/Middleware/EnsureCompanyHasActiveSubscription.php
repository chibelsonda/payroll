<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyHasActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->attributes->get('active_company');

        if (!$company) {
            return ApiResponse::error(
                'Company context is required',
                [],
                [],
                Response::HTTP_FORBIDDEN
            );
        }

        $subscription = \App\Models\Subscription::where('company_id', $company->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if (!$subscription) {
            return ApiResponse::error(
                'Active subscription is required to access this resource',
                [],
                [
                    'subscription_required' => true,
                    'redirect_to' => '/billing',
                ],
                Response::HTTP_PAYMENT_REQUIRED
            );
        }

        return $next($request);
    }
}
