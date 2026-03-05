<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('telescope') || $request->is('telescope/*')) {
            return $next($request);
        }

        $host = $request->getHost();
        $baseDomain = config('app.base_domain');

        if (! str_ends_with($host, $baseDomain)) {
            abort(404);
        }

        $subdomain = str_replace('.' . $baseDomain, '', $host);

        if ($subdomain === $baseDomain || $subdomain === '') {
            abort(404);
        }

        $slug = explode('.', $subdomain)[0];

        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->active()
            ->first();

        if (! $tenant) {
            abort(404);
        }

        TenantContext::set($tenant);

        return $next($request);
    }
}
