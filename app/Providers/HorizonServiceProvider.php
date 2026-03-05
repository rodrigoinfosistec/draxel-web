<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Horizon::auth(function ($request) {
            $tenant = TenantContext::current();

            if (! $tenant || $tenant->slug !== 'dpanel') {
                return false;
            }

            $user = $request->user();

            return $user && $user->is_admin;
        });
    }

    /**
     * Register the Horizon gate.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            $tenant = TenantContext::current();

            return $tenant
                && $tenant->slug === 'dpanel'
                && $user
                && $user->is_admin;
        });
    }
}
