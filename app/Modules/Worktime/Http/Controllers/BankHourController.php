<?php

namespace App\Modules\Worktime\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Modules\Worktime\Http\Requests\StoreBankHourEntryRequest;
use App\Modules\Worktime\Http\Requests\UpdateBankHourEntryRequest;
use App\Modules\Worktime\Models\BankHourAccount;
use App\Modules\Worktime\Models\BankHourEntry;
use App\Modules\Worktime\Services\BankHourService;
use App\Support\Audit\Audit;
use App\Support\CompanyContext;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        abort_unless($company->uses_hour_bank, 404);

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

        $accounts = $this->buildAccounts(
            tenantId: $request->user()->tenant_id,
            companyId: $company->id,
            employeeId: $employeeId,
            startDate: $startDate,
            endDate: $endDate,
        );

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

    public function create(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('worktime.createBankHourEntry'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);
        abort_unless($company->uses_hour_bank, 404);

        $employees = Employee::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
            ]);

        return Inertia::render('worktime/bank-hours/Create', [
            'employees' => $employees,
            'entryTypes' => [
                ['value' => 'manual_credit', 'label' => 'Crédito manual'],
                ['value' => 'manual_debit', 'label' => 'Débito manual'],
            ],
        ]);
    }

    public function store(StoreBankHourEntryRequest $request): RedirectResponse
    {
        $company = CompanyContext::current();

        abort_unless($company, 404);
        abort_unless($company->uses_hour_bank, 404);

        $validated = $request->validated();

        $entry = $this->service->registerManualEntry(
            tenantId: $request->user()->tenant_id,
            companyId: $company->id,
            employeeId: (int) $validated['employee_id'],
            entryType: $validated['entry_type'],
            hours: $validated['hours'],
            occurredOn: $validated['occurred_on'],
            description: $validated['description'] ?? null,
            user: $request->user(),
        );

        Audit::event('worktime.bank-hour-entries.created', $entry, [
            'employee_id' => $entry->employee_id,
            'entry_type' => $entry->entry_type?->value,
            'minutes' => $entry->minutes,
            'occurred_on' => $entry->occurred_on?->format('Y-m-d'),
        ]);

        return redirect()
            ->route('worktime.bank-hours.index')
            ->with('alert', Flash::success('Lançamento criado', 'O movimento do banco de horas foi registrado com sucesso.'));
    }

    public function edit(Request $request, BankHourEntry $bankHourEntry): Response
    {
        abort_unless($request->user()->hasPermission('worktime.updateBankHourEntry'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);
        abort_unless($company->uses_hour_bank, 404);
        abort_unless(
            $bankHourEntry->tenant_id === $request->user()->tenant_id
            && $bankHourEntry->company_id === $company->id,
            404
        );

        abort_unless(in_array($bankHourEntry->entry_type?->value, ['manual_credit', 'manual_debit'], true), 422);

        return Inertia::render('worktime/bank-hours/Edit', [
            'entry' => [
                'id' => $bankHourEntry->id,
                'employee_name' => $bankHourEntry->employee?->name,
                'entry_type' => $bankHourEntry->entry_type?->value,
                'hours' => $this->service->absoluteMinutesToHours($bankHourEntry->minutes),
                'occurred_on' => $bankHourEntry->occurred_on?->format('Y-m-d'),
                'description' => $bankHourEntry->description,
            ],
            'entryTypes' => [
                ['value' => 'manual_credit', 'label' => 'Crédito manual'],
                ['value' => 'manual_debit', 'label' => 'Débito manual'],
            ],
        ]);
    }

    public function update(UpdateBankHourEntryRequest $request, BankHourEntry $bankHourEntry): RedirectResponse
    {
        $company = CompanyContext::current();

        abort_unless($company, 404);
        abort_unless($company->uses_hour_bank, 404);
        abort_unless(
            $bankHourEntry->tenant_id === $request->user()->tenant_id
            && $bankHourEntry->company_id === $company->id,
            404
        );

        $validated = $request->validated();

        $entry = $this->service->updateManualEntry(
            entry: $bankHourEntry,
            entryType: $validated['entry_type'],
            hours: $validated['hours'],
            occurredOn: $validated['occurred_on'],
            description: $validated['description'] ?? null,
        );

        Audit::event('worktime.bank-hour-entries.updated', $entry, [
            'entry_type' => $entry->entry_type?->value,
            'minutes' => $entry->minutes,
            'occurred_on' => $entry->occurred_on?->format('Y-m-d'),
        ]);

        return redirect()
            ->route('worktime.bank-hours.index')
            ->with('alert', Flash::success('Lançamento atualizado', 'O movimento do banco de horas foi atualizado com sucesso.'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('worktime.exportBankHour'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);
        abort_unless($company->uses_hour_bank, 404);

        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $accounts = $this->buildAccounts(
            tenantId: $request->user()->tenant_id,
            companyId: $company->id,
            employeeId: isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
        );

        $filename = 'bank-hours-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($accounts) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Funcionário',
                'Saldo inicial do período',
                'Saldo atual',
                'Movimentos no período',
            ], ';');

            foreach ($accounts as $account) {
                fputcsv($handle, [
                    $account['employee_name'],
                    $account['opening_balance_label'],
                    $account['current_balance_label'],
                    count($account['entries']),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        abort_unless($request->user()->hasPermission('worktime.exportBankHour'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);
        abort_unless($company->uses_hour_bank, 404);

        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $accounts = $this->buildAccounts(
            tenantId: $request->user()->tenant_id,
            companyId: $company->id,
            employeeId: isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
        );

        $pdf = Pdf::setOption([
            'isPhpEnabled' => false,
        ])
            ->loadView('pdf.bank-hours-report', [
                'accounts' => $accounts,
                'filters' => [
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ],
                'generatedAt' => now()->format('d/m/Y H:i:s'),
                'tenantName' => $request->user()->tenant?->name ?? 'Tenant',
                'companyName' => $company->name ?? 'Empresa',
            ])
            ->setPaper('a4', 'landscape');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans Mono', 'normal');

        $canvas->page_text(
            680,
            560,
            '{PAGE_NUM}/{PAGE_COUNT}',
            $font,
            9,
            [0.42, 0.45, 0.5]
        );

        return response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="bank-hours-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function exportEmployeePdf(Request $request, BankHourAccount $bankHourAccount)
    {
        abort_unless($request->user()->hasPermission('worktime.exportBankHour'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);
        abort_unless($company->uses_hour_bank, 404);
        abort_unless(
            $bankHourAccount->tenant_id === $request->user()->tenant_id
            && $bankHourAccount->company_id === $company->id,
            404
        );

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $account = collect($this->buildAccounts(
            tenantId: $request->user()->tenant_id,
            companyId: $company->id,
            employeeId: (int) $bankHourAccount->employee_id,
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
        ))->firstWhere('id', $bankHourAccount->id);

        abort_unless($account, 404);

        $pdf = Pdf::setOption([
            'isPhpEnabled' => false,
        ])
            ->loadView('pdf.bank-hour-employee-report', [
                'account' => $account,
                'filters' => [
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ],
                'generatedAt' => now()->format('d/m/Y H:i:s'),
                'tenantName' => $request->user()->tenant?->name ?? 'Tenant',
                'companyName' => $company->name ?? 'Empresa',
            ])
            ->setPaper('a4', 'landscape');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans Mono', 'normal');

        $canvas->page_text(
            680,
            560,
            '{PAGE_NUM}/{PAGE_COUNT}',
            $font,
            9,
            [0.42, 0.45, 0.5]
        );

        $safeName = str($account['employee_name'] ?? 'funcionario')
            ->ascii()
            ->lower()
            ->replace(' ', '-')
            ->value();

        return response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="bank-hour-' . $safeName . '-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    protected function buildAccounts(
        int $tenantId,
        int $companyId,
        ?int $employeeId,
        string $startDate,
        string $endDate,
    ): array {
        return BankHourAccount::query()
            ->with([
                'employee',
                'entries' => fn ($query) => $query
                    ->whereBetween('occurred_on', [$startDate, $endDate])
                    ->orderBy('occurred_on')
                    ->orderBy('id'),
            ])
            ->join('employees', 'employees.id', '=', 'bank_hour_accounts.employee_id')
            ->select('bank_hour_accounts.*')
            ->where('bank_hour_accounts.tenant_id', $tenantId)
            ->where('bank_hour_accounts.company_id', $companyId)
            ->when($employeeId, fn ($query) => $query->where('bank_hour_accounts.employee_id', $employeeId))
            ->orderBy('employees.name')
            ->get()
            ->map(function ($account) use ($startDate) {
                $openingBalanceMinutes = (int) BankHourEntry::query()
                    ->where('bank_hour_account_id', $account->id)
                    ->whereDate('occurred_on', '<', $startDate)
                    ->sum('minutes');

                $runningBalanceMinutes = $openingBalanceMinutes;

                $entries = $account->entries->map(function ($entry) use (&$runningBalanceMinutes) {
                    $runningBalanceMinutes += (int) $entry->minutes;

                    return [
                        'id' => $entry->id,
                        'occurred_on' => $entry->occurred_on?->format('d/m/Y'),
                        'entry_type' => $entry->entry_type?->value,
                        'entry_type_label' => $entry->entry_type?->label(),
                        'minutes' => (int) $entry->minutes,
                        'minutes_label' => $this->service->formatMinutes((int) $entry->minutes),
                        'running_balance_minutes' => $runningBalanceMinutes,
                        'running_balance_label' => $this->service->formatMinutes($runningBalanceMinutes),
                        'description' => $entry->description,
                        'can_edit' => in_array($entry->entry_type?->value, ['manual_credit', 'manual_debit'], true),
                    ];
                })->values()->all();

                return [
                    'id' => $account->id,
                    'employee_name' => $account->employee?->name,
                    'current_balance_minutes' => (int) $account->current_balance_minutes,
                    'current_balance_label' => $this->service->formatMinutes((int) $account->current_balance_minutes),
                    'opening_balance_minutes' => $openingBalanceMinutes,
                    'opening_balance_label' => $this->service->formatMinutes($openingBalanceMinutes),
                    'entries' => $entries,
                ];
            })
            ->values()
            ->all();
    }
}
