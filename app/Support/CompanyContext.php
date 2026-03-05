<?php

namespace App\Support;

use App\Models\Company;

class CompanyContext
{
    public static function current(): ?Company
    {
        return app()->bound('currentCompany')
            ? app('currentCompany')
            : null;
    }

    public static function id(): ?int
    {
        return static::current()?->id;
    }

    public static function set(Company $company): void
    {
        app()->instance('currentCompany', $company);
    }

    public static function has(): bool
    {
        return app()->bound('currentCompany');
    }
}
