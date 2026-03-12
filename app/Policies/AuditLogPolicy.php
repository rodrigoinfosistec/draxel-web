<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.viewAny');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->tenant_id === $auditLog->tenant_id
            && $user->hasPermission('audit.view');
    }
}
