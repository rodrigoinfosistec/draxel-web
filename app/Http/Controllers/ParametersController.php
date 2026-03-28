<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyDefaultTimesRequest;
use App\Http\Requests\UpdateCompanyHourBankRequest;
use App\Models\CompanyDefaultTime;
use App\Support\Audit\Audit;
use App\Support\CompanyContext;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ParametersController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('parameters.view'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);

        $times = $company->defaultTimes()
            ->get()
            ->sortBy(fn (CompanyDefaultTime $time) => $time->weekday->order())
            ->values();

        $weeklyWorkloadMinutes = $times->sum(function (CompanyDefaultTime $time) {
            if (! $time->start_time || ! $time->end_time) {
                return 0;
            }

            $startMinutes = $this->timeToMinutes($time->start_time);
            $endMinutes = $this->timeToMinutes($time->end_time);
            $breakMinutes = $time->break_duration ? $this->timeToMinutes($time->break_duration) : 0;

            if ($endMinutes <= $startMinutes) {
                return 0;
            }

            return max(0, ($endMinutes - $startMinutes) - $breakMinutes);
        });

        return Inertia::render('parameters/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'uses_hour_bank' => (bool) $company->uses_hour_bank,
                'hour_bank_starts_at' => $company->hour_bank_starts_at?->format('Y-m-d'),
            ],
            'tabs' => [
                [
                    'key' => 'company-default-times',
                    'label' => 'Horários padrão',
                ],
                [
                    'key' => 'company-hour-bank',
                    'label' => 'Banco de horas',
                ],
            ],
            'defaultTimes' => $times->map(fn (CompanyDefaultTime $time) => [
                'weekday' => $time->weekday->value,
                'weekday_label' => $time->weekday->label(),
                'start_time' => $time->start_time,
                'end_time' => $time->end_time,
                'break_duration' => $time->break_duration,
            ]),
            'weeklyWorkload' => [
                'minutes' => $weeklyWorkloadMinutes,
                'label' => $this->formatMinutesToHuman($weeklyWorkloadMinutes),
            ],
        ]);
    }

    public function updateCompanyDefaultTimes(UpdateCompanyDefaultTimesRequest $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('parameters.update'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);

        DB::transaction(function () use ($request, $company) {
            foreach ($request->validated('times') as $time) {
                CompanyDefaultTime::query()->updateOrCreate(
                    [
                        'tenant_id' => $company->tenant_id,
                        'company_id' => $company->id,
                        'weekday' => $time['weekday'],
                    ],
                    [
                        'start_time' => $time['start_time'],
                        'end_time' => $time['end_time'],
                        'break_duration' => $time['break_duration'],
                    ],
                );
            }

            Audit::event('parameters.company_default_times_updated', $company, [
                'company_id' => $company->id,
                'company_name' => $company->name,
            ]);
        });

        return redirect()
            ->route('parameters.index')
            ->with('alert', Flash::success(
                'Horários padrão atualizados',
                'Os horários padrão da empresa foram atualizados com sucesso.'
            ));
    }

    public function updateCompanyHourBank(UpdateCompanyHourBankRequest $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('parameters.update'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);

        DB::transaction(function () use ($request, $company) {
            $before = [
                'uses_hour_bank' => (bool) $company->uses_hour_bank,
                'hour_bank_starts_at' => $company->hour_bank_starts_at?->format('Y-m-d'),
            ];

            $company->update([
                'uses_hour_bank' => $request->boolean('uses_hour_bank'),
                'hour_bank_starts_at' => $request->boolean('uses_hour_bank')
                    ? $request->validated('hour_bank_starts_at')
                    : null,
            ]);

            Audit::event('parameters.company_hour_bank_updated', $company, [
                'before' => $before,
                'after' => [
                    'uses_hour_bank' => (bool) $company->uses_hour_bank,
                    'hour_bank_starts_at' => $company->hour_bank_starts_at?->format('Y-m-d'),
                ],
            ]);
        });

        return redirect()
            ->route('parameters.index')
            ->with('alert', Flash::success(
                'Banco de horas atualizado',
                'As configurações do banco de horas foram atualizadas com sucesso.'
            ));
    }

    protected function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours * 60) + (int) $minutes;
    }

    protected function formatMinutesToHuman(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return sprintf('%dh', $hours);
        }

        return sprintf('%dh%02dmin', $hours, $remainingMinutes);
    }
}
