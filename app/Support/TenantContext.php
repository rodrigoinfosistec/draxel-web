<?php

namespace App\Support;

use App\Models\Tenant;

class TenantContext
{
    public static function current(): ?Tenant
    {
        return app()->bound('currentTenant')
            ? app('currentTenant')
            : null;
    }

    public static function id(): ?int
    {
        return static::current()?->id;
    }

    public static function set(Tenant $tenant): void
    {
        app()->instance('currentTenant', $tenant);
    }

    public static function has(): bool
    {
        return app()->bound('currentTenant');
    }
}
