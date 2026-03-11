<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Session;

class ClearUserPermissionsFromSession
{
    public function handle(Logout $event): void
    {
        Session::forget('auth_permissions');
        Session::forget('auth_permissions_tenant_id');
    }
}
