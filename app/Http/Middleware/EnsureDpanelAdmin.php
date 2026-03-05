<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDpanelAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::current();
        $user = $request->user();

        if (! $tenant || $tenant->slug !== 'dpanel') {
            abort(403);
        }

        if (! $user || ! $user->is_admin) {
            abort(403);
        }

        return $next($request);
    }
}
