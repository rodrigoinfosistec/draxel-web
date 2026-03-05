<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDpanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::current();

        if (! $tenant || $tenant->slug !== 'dpanel') {
            abort(403);
        }

        return $next($request);
    }
}
