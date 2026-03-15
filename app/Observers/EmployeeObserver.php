<?php

namespace App\Observers;

use App\Models\CompanyDefaultTime;
use App\Models\Employee;

class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        $defaultTimes = CompanyDefaultTime::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('company_id', $employee->company_id)
            ->orderBy('id')
            ->get();

        if ($defaultTimes->isEmpty()) {
            return;
        }

        $employee->employeeTimes()->createMany(
            $defaultTimes->map(fn (CompanyDefaultTime $defaultTime) => [
                'tenant_id' => $employee->tenant_id,
                'company_id' => $employee->company_id,
                'weekday' => $defaultTime->weekday->value,
                'start_time' => $defaultTime->start_time,
                'end_time' => $defaultTime->end_time,
                'break_duration' => $defaultTime->break_duration,
            ])->toArray()
        );
    }
}
