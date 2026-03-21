<?php

namespace App\Modules\Worktime\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Modules\Worktime\Services\BankHourService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankHourController extends Controller
{
    public function __construct(
        protected BankHourService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('worktime.viewAnyBankHour'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);

        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $employeeId = isset($validated['employee_id']) ? (int) $validated['employee_id'] : null;
        $startDate = $validated['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? now()->endOfMonth()->format('Y-m-d');

        $employees = Employee::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
            ]);

        $accounts = \App\Modules\Worktime\Models\BankHourAccount::query()
            ->with([
                'employee',
                'entries' => fn ($query) => $query
                    ->whereBetween('occurred_on', [$startDate, $endDate])
                    ->latest('occurred_on')
                    ->latest('id'),
            ])
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('company_id', $company->id)
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->orderBy('employee_id')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'employee_name' => $account->employee?->name,
                    'current_balance_minutes' => $account->current_balance_minutes,
                    'current_balance_label' => $this->service->formatMinutes($account->current_balance_minutes),
                    'entries' => $account->entries->map(fn ($entry) => [
                        'id' => $entry->id,
                        'occurred_on' => $entry->occurred_on?->format('d/m/Y'),
                        'entry_type' => $entry->entry_type?->value,
                        'entry_type_label' => $entry->entry_type?->label(),
                        'minutes' => $entry->minutes,
                        'minutes_label' => $this->service->formatMinutes($entry->minutes),
                        'description' => $entry->description,
                    ])->values(),
                ];
            })
            ->values();

        return Inertia::render('worktime/bank-hours/Index', [
            'filters' => [
                'employee_id' => $employeeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'employees' => $employees,
            'accounts' => $accounts,
        ]);
    }
}
