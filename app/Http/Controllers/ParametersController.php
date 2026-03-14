<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyDefaultTimesRequest;
use App\Models\CompanyDefaultTime;
use App\Support\Audit\Audit;
use App\Support\CompanyContext;
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
            ->values()
            ->map(fn (CompanyDefaultTime $time) => [
                'weekday' => $time->weekday->value,
                'weekday_label' => $time->weekday->label(),
                'start_time' => $time->start_time,
                'end_time' => $time->end_time,
                'break_duration' => $time->break_duration,
            ]);

        return Inertia::render('parameters/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'tabs' => [
                [
                    'key' => 'company-default-times',
                    'label' => 'Horários padrão',
                ],
            ],
            'defaultTimes' => $times,
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

        return back()->with('success', 'Horários padrão atualizados com sucesso.');
    }
}
