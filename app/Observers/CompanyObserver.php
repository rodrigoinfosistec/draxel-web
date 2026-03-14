<?php

namespace App\Observers;

use App\Enums\Weekday;
use App\Models\Company;
use App\Models\CompanyDefaultTime;

class CompanyObserver
{
    public function created(Company $company): void
    {
        $defaults = config('company.time_default', []);

        foreach (Weekday::cases() as $weekday) {
            $dayConfig = $defaults[$weekday->value] ?? [
                'start' => null,
                'end' => null,
                'break' => null,
            ];

            CompanyDefaultTime::query()->create([
                'tenant_id' => $company->tenant_id,
                'company_id' => $company->id,
                'weekday' => $weekday,
                'start_time' => $dayConfig['start'],
                'end_time' => $dayConfig['end'],
                'break_duration' => $dayConfig['break'],
            ]);
        }
    }
}
