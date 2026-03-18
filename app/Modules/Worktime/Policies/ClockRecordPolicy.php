<?php

namespace App\Modules\Worktime\Policies;

use App\Models\User;
use App\Modules\Worktime\Models\ClockRecord;

class ClockRecordPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->hasPermission('worktime.viewAnyClockRecord');
    }

    public function view(User $user, ClockRecord $clockRecord): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $clockRecord->tenant_id
            && session('current_company_id') === $clockRecord->company_id
            && $user->hasPermission('worktime.viewClockRecord');
    }

    public function create(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->hasPermission('worktime.createClockRecord');
    }

    public function update(User $user, ClockRecord $clockRecord): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $clockRecord->tenant_id
            && session('current_company_id') === $clockRecord->company_id
            && $user->hasPermission('worktime.updateClockRecord');
    }

    public function delete(User $user, ClockRecord $clockRecord): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $clockRecord->tenant_id
            && session('current_company_id') === $clockRecord->company_id
            && $user->hasPermission('worktime.deleteClockRecord');
    }
}
