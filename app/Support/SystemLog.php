<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class SystemLog
{
    public static function error(string $message, array $data = []): void
    {
        Log::error($message, self::context($data));
    }

    public static function warning(string $message, array $data = []): void
    {
        Log::warning($message, self::context($data));
    }

    public static function info(string $message, array $data = []): void
    {
        Log::info($message, self::context($data));
    }

    public static function debug(string $message, array $data = []): void
    {
        Log::debug($message, self::context($data));
    }

    protected static function context(array $data): array
    {
        return array_merge([
            'tenant_id' => TenantContext::id(),
            'company_id' => CompanyContext::id(),
            'user_id' => Auth::id(),

            'route' => Request::route()?->uri(),
            'method' => Request::method(),
        ], $data);
    }
}
