<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeTimesRequest;
use App\Models\Employee;
use App\Support\Audit\Audit;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeTimeController extends Controller
{
    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        $employee->load('employeeTimes');

        return Inertia::render('employees/Times', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'cpf' => $employee->cpf,
                'registration' => $employee->registration,
            ],
            'times' => $employee->employeeTimes
                ->sortBy(fn ($time) => $time->weekday->value)
                ->values()
                ->map(fn ($time) => [
                    'weekday' => $time->weekday->value,
                    'weekday_label' => $time->weekday->label(),
                    'start_time' => $time->start_time,
                    'end_time' => $time->end_time,
                    'break_duration' => $time->break_duration,
                ]),
        ]);
    }

    public function update(UpdateEmployeeTimesRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('update', $employee);

        DB::transaction(function () use ($request, $employee) {
            $before = $employee->employeeTimes()
                ->get()
                ->map(fn ($time) => [
                    'weekday' => $time->weekday->value,
                    'start_time' => $time->start_time,
                    'end_time' => $time->end_time,
                    'break_duration' => $time->break_duration,
                ])
                ->toArray();

            foreach ($request->validated('times') as $timeData) {
                $employee->employeeTimes()
                    ->where('weekday', $timeData['weekday'])
                    ->update([
                        'start_time' => $timeData['start_time'] ?: null,
                        'end_time' => $timeData['end_time'] ?: null,
                        'break_duration' => $timeData['break_duration'] ?: null,
                    ]);
            }

            $after = $employee->employeeTimes()
                ->get()
                ->map(fn ($time) => [
                    'weekday' => $time->weekday->value,
                    'start_time' => $time->start_time,
                    'end_time' => $time->end_time,
                    'break_duration' => $time->break_duration,
                ])
                ->toArray();

            Audit::event('employees.times.updated', $employee, [
                'before' => $before,
                'after' => $after,
            ]);
        });

        return redirect()
            ->route('employees.index')
            ->with('alert', Flash::success('Horários atualizados', 'Os horários do funcionário foram atualizados com sucesso.'));
    }
}
