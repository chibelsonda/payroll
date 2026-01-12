<?php

namespace App\Http\Middleware;

use App\Models\Company;
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
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Use user's company_id directly (users belong to one company)
        if (!$user->company_id) {
            abort(403, 'User does not belong to a company');
        }

        // Find company by ID
        $company = Company::find($user->company_id);

        if (!$company) {
            abort(404, 'Company not found');
        }

        // Store active company ID globally
        app()->instance('active_company_id', $company->id);

        // Set Spatie team context
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);

        // Make company available in request
        $request->attributes->set('active_company', $company);

        return $next($request);
    }
}
