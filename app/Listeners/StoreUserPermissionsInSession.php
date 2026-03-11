<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;

class StoreUserPermissionsInSession
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $tenantId = TenantContext::id();

        if (! $tenantId) {
            Session::forget('auth_permissions');
            Session::forget('auth_permissions_tenant_id');

            return;
        }

        Session::put('auth_permissions', $user->getAllPermissionSlugs());
        Session::put('auth_permissions_tenant_id', $tenantId);
    }
}
