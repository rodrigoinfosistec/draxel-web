<?php

namespace App\Http\Middleware;

use App\Support\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $companyId = session('current_company_id');

        if (! $companyId) {
            $companyId = $user->default_company_id;
        }

        $company = $user->companies()
            ->where('companies.id', $companyId)
            ->first();

        if (! $company) {
            abort(403);
        }

        session(['current_company_id' => $company->id]);

        CompanyContext::set($company);

        return $next($request);
    }
}
