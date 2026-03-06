<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Support\TenantContext;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Audit
{
    public static function event(
        string $event,
        ?Model $subject = null,
        array $properties = []
    ): void {
        $tenantId = TenantContext::id();
        if (!$tenantId) {
            return;
        }

        $userId = Auth::id();

        $subjectType = $subject ? $subject::class : null;
        $subjectId = $subject ? (string) $subject->getKey() : null;

        AuditLog::create([
            'tenant_id' => $tenantId,
            'company_id' => CompanyContext::id(),
            'user_id' => $userId,

            'event' => $event,

            'subject_type' => $subjectType,
            'subject_id' => $subjectId,

            'route' => Request::route()?->uri(),
            'method' => Request::method(),

            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),

            'properties' => $properties ?: null,
        ]);
    }
}
