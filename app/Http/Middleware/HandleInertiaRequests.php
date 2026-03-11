<?php

namespace App\Http\Middleware;

use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user()
                    ? $request->user()->getAllPermissionSlugs()
                    : [],
            ],
            'flash' => [
                'alert' => fn () => $request->session()->get('alert'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            'tenant' => function () {
                $tenant = TenantContext::current();

                return $tenant ? [
                    'id'   => $tenant->id,
                    'slug' => $tenant->slug,
                    'name' => $tenant->name,
                ] : null;
            },

            'companies' => function () use ($request) {
                if (! $request->user()) {
                    return [];
                }

                return $request->user()
                    ->companies()
                    ->select('companies.id', 'companies.name')
                    ->get()
                    ->toArray();
            },

            'currentCompanyId' => function () {
                return CompanyContext::id();
            },

            'currentCompany' => function () {
                $company = CompanyContext::current();

                return $company ? [
                    'id' => $company->id,
                    'name' => $company->name,
                    'color' => $company->color,
                ] : null;
            },

            'defaultCompanyId' => function () use ($request) {
                return $request->user()?->default_company_id;
            },
        ];
    }
}
