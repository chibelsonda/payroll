<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Get company UUID from X-Company-ID header or query parameter
        $companyUuid = $request->header('X-Company-ID') ?: $request->query('company_id');

        // For /api/v1/user endpoint, make company optional (UserResource will use fallback)
        $isUserEndpoint = $request->routeIs('v1.auth.user')
            || $request->is('api/v1/user')
            || $request->path() === 'api/v1/user';

        // If no company UUID provided and it's the user endpoint, skip company validation
        if (!$companyUuid && $isUserEndpoint) {
            return $next($request);
        }

        // If company UUID is provided but it's the user endpoint, still validate
        // but allow it if user doesn't belong to company yet (they might have just been added)
        if ($companyUuid && $isUserEndpoint) {
            $company = Company::where('uuid', $companyUuid)->first();
            if ($company) {
                $userBelongsToCompany = $user->companies()->where('companies.id', $company->id)->exists();
                // If user belongs to company, set it as active
                if ($userBelongsToCompany) {
                    app()->instance('active_company_id', $company->id);
                    app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
                    $request->attributes->set('active_company', $company);
                }
                // If user doesn't belong yet, continue anyway (they might have just been added)
                // Don't abort - let the endpoint handle it
                return $next($request);
            }
        }

        if (!$companyUuid && !$isUserEndpoint) {
            abort(403, 'Company ID is required. Please provide X-Company-ID header.');
        }

        // Find company by UUID
        $company = Company::where('uuid', $companyUuid)->first();

        if (!$company) {
            abort(404, 'Company not found');
        }

        // Verify user belongs to this company
        $userBelongsToCompany = $user->companies()->where('companies.id', $company->id)->exists();

        if (!$userBelongsToCompany) {
            abort(403, 'User does not belong to this company');
        }

        // Store active company ID globally and set Spatie team context
        app()->instance('active_company_id', $company->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

        // Make company available in request
        $request->attributes->set('active_company', $company);

        return $next($request);
    }
}
