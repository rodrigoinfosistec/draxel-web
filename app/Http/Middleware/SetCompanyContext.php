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

        $company = null;

        $sessionCompanyId = session('current_company_id');

        if ($sessionCompanyId) {
            $company = $user->companies()
                ->where('companies.id', $sessionCompanyId)
                ->first();
        }

        if (! $company && $user->default_company_id) {
            $company = $user->companies()
                ->where('companies.id', $user->default_company_id)
                ->first();
        }

        if (! $company) {
            $company = $user->companies()->first();
        }

        if (! $company) {
            session()->forget('current_company_id');
            abort(403, 'Usuário sem empresa vinculada.');
        }

        session(['current_company_id' => $company->id]);

        CompanyContext::set($company);

        return $next($request);
    }
}
