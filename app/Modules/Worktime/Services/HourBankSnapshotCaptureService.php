<?php

namespace App\Modules\Worktime\Services;

use App\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class HourBankSnapshotCaptureService
{
    public function __construct(
        protected HourBankSnapshotDayApurationService $dayApurationService,
    ) {
    }

    public function capture(Employee $employee, string $periodStart, string $periodEnd): array
    {
        $startDate = Carbon::parse($periodStart)->startOfDay();
        $endDate = Carbon::parse($periodEnd)->endOfDay();

        $days = collect();

        foreach (CarbonPeriod::create($startDate->toDateString(), $endDate->toDateString()) as $date) {
            $days->push(
                $this->dayApurationService->apurate(
                    employee: $employee,
                    date: Carbon::instance($date),
                ),
            );
        }

        return [
            'employee_name' => $employee->name,
            'employee_registration' => $employee->registration,
            'captured_at' => now(),
            'days' => $days->values()->all(),
            'totals' => [
                'justified_minutes' => (int) $days->sum('justified_minutes'),
                'late_minutes' => (int) $days->sum('late_minutes'),
                'extra_minutes' => (int) $days->sum('extra_minutes'),
                'absence_minutes' => (int) $days->sum('absence_minutes'),
                'suspension_minutes' => (int) $days->sum('suspension_minutes'),
                'balance_minutes' => (int) $days->sum('balance_minutes'),
                'has_divergence' => $days->contains(fn (array $day) => $day['has_divergence'] === true),
                'divergence_summary' => $this->buildDivergenceSummary($days),
            ],
        ];
    }

    protected function buildDivergenceSummary(Collection $days): ?string
    {
        $dates = $days
            ->filter(fn (array $day) => $day['has_divergence'] === true)
            ->map(fn (array $day) => Carbon::parse($day['work_date'])->format('d/m/Y'))
            ->values();

        if ($dates->isEmpty()) {
            return null;
        }

        return 'Divergências nas datas: ' . $dates->implode(', ');
    }
}
