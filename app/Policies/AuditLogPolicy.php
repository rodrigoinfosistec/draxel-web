<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array('audit.viewAny', $user->getAllPermissionSlugs(), true);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->tenant_id === $auditLog->tenant_id
            && in_array('audit.view', $user->getAllPermissionSlugs(), true);
    }
}
