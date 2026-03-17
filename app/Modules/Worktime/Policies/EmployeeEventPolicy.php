<?php

namespace App\Modules\Worktime\Policies;

use App\Models\User;
use App\Modules\Worktime\Models\EmployeeEvent;

class EmployeeEventPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->hasPermission('worktime.viewAny');
    }

    public function view(User $user, EmployeeEvent $employeeEvent): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $employeeEvent->tenant_id
            && session('current_company_id') === $employeeEvent->company_id
            && $user->hasPermission('worktime.view');
    }

    public function create(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->hasPermission('worktime.create');
    }

    public function update(User $user, EmployeeEvent $employeeEvent): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $employeeEvent->tenant_id
            && session('current_company_id') === $employeeEvent->company_id
            && $user->hasPermission('worktime.update');
    }

    public function delete(User $user, EmployeeEvent $employeeEvent): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $employeeEvent->tenant_id
            && session('current_company_id') === $employeeEvent->company_id
            && $user->hasPermission('worktime.delete');
    }
}
