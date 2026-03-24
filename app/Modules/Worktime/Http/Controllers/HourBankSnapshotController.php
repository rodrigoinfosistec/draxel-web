<?php

namespace App\Modules\Worktime\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Modules\Worktime\Http\Requests\AddHourBankSnapshotEmployeesRequest;
use App\Modules\Worktime\Http\Requests\StoreHourBankSnapshotRequest;
use App\Modules\Worktime\Models\HourBankSnapshot;
use App\Modules\Worktime\Models\HourBankSnapshotEmployee;
use App\Modules\Worktime\Services\HourBankSnapshotService;
use App\Support\Audit\Audit;
use App\Support\CompanyContext;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HourBankSnapshotController extends Controller
{
    public function __construct(
        protected HourBankSnapshotService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('worktime.viewAnyHourBankSnapshot'), 403);

        $snapshots = $this->filteredQuery($request)
            ->withCount('employees')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (HourBankSnapshot $snapshot) => [
                'id' => $snapshot->id,
                'name' => $snapshot->name,
                'period_start' => $snapshot->period_start?->format('d/m/Y'),
                'period_end' => $snapshot->period_end?->format('d/m/Y'),
                'status' => $snapshot->status->value,
                'status_label' => $snapshot->status->label(),
                'employees_count' => $snapshot->employees_count,
                'consolidated_at' => $snapshot->consolidated_at?->format('d/m/Y H:i'),
                'reversed_at' => $snapshot->reversed_at?->format('d/m/Y H:i'),
                'can_delete' => $snapshot->status->isEditable(),
            ]);

        return Inertia::render('worktime/hour-bank-snapshots/Index', [
            'snapshots' => $snapshots,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('worktime.createHourBankSnapshot'), 403);

        return Inertia::render('worktime/hour-bank-snapshots/Create');
    }

    public function store(StoreHourBankSnapshotRequest $request): RedirectResponse
    {
        $snapshot = $this->service->create(
            data: $request->validated(),
            user: $request->user(),
        );

        Audit::event('worktime.hour-bank-snapshots.created', $snapshot, [
            'snapshot_id' => $snapshot->id,
            'name' => $snapshot->name,
            'period_start' => $snapshot->period_start?->format('Y-m-d'),
            'period_end' => $snapshot->period_end?->format('Y-m-d'),
        ]);

        return redirect()
            ->route('worktime.hour-bank-snapshots.show', $snapshot)
            ->with('alert', Flash::success(
                'Fechamento criado',
                'O fechamento foi criado com sucesso.'
            ));
    }

    public function show(Request $request, HourBankSnapshot $hourBankSnapshot): Response
    {
        abort_unless($request->user()->hasPermission('worktime.viewHourBankSnapshot'), 403);
        $this->ensureSnapshotContext($request, $hourBankSnapshot);

        $hourBankSnapshot->load([
            'employees.days',
        ]);

        $validation = $this->service->validateConsolidation($hourBankSnapshot);

        $includedEmployeeIds = $hourBankSnapshot->employees
            ->pluck('employee_id')
            ->all();

        $availableEmployees = Employee::query()
            ->where('tenant_id', $hourBankSnapshot->tenant_id)
            ->where('company_id', $hourBankSnapshot->company_id)
            ->when(! empty($includedEmployeeIds), function ($query) use ($includedEmployeeIds) {
                $query->whereNotIn('id', $includedEmployeeIds);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'registration'])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'registration' => $employee->registration,
                'label' => trim($employee->name . ($employee->registration ? ' - ' . $employee->registration : '')),
            ])
            ->values();

        return Inertia::render('worktime/hour-bank-snapshots/Show', [
            'snapshot' => [
                'id' => $hourBankSnapshot->id,
                'name' => $hourBankSnapshot->name,
                'period_start' => $hourBankSnapshot->period_start?->format('Y-m-d'),
                'period_end' => $hourBankSnapshot->period_end?->format('Y-m-d'),
                'period_label' => $hourBankSnapshot->period_start?->format('d/m/Y') . ' a ' . $hourBankSnapshot->period_end?->format('d/m/Y'),
                'status' => $hourBankSnapshot->status->value,
                'status_label' => $hourBankSnapshot->status->label(),
                'notes' => $hourBankSnapshot->notes,
                'is_editable' => $hourBankSnapshot->status->isEditable(),
                'is_consolidated' => $hourBankSnapshot->status->isConsolidated(),
                'can_generate_preview_general' => ! $hourBankSnapshot->employees->contains(
                    fn (HourBankSnapshotEmployee $employee) => $employee->has_divergence
                ),
                'can_consolidate' => $validation['can_consolidate'],
                'validation_errors' => $validation['errors'],
                'duplicate_dates' => $validation['duplicate_dates'],
                'consolidated_at' => $hourBankSnapshot->consolidated_at?->format('d/m/Y H:i'),
                'reversed_at' => $hourBankSnapshot->reversed_at?->format('d/m/Y H:i'),
                'reversal_reason' => $hourBankSnapshot->reversal_reason,
            ],
            'employees' => $hourBankSnapshot->employees
                ->sortBy('employee_name')
                ->map(fn (HourBankSnapshotEmployee $snapshotEmployee) => [
                    'id' => $snapshotEmployee->id,
                    'employee_id' => $snapshotEmployee->employee_id,
                    'employee_name' => $snapshotEmployee->employee_name,
                    'employee_registration' => $snapshotEmployee->employee_registration,
                    'justified_minutes' => $snapshotEmployee->justified_minutes,
                    'late_minutes' => $snapshotEmployee->late_minutes,
                    'extra_minutes' => $snapshotEmployee->extra_minutes,
                    'absence_minutes' => $snapshotEmployee->absence_minutes,
                    'suspension_minutes' => $snapshotEmployee->suspension_minutes,
                    'balance_minutes' => $snapshotEmployee->balance_minutes,
                    'has_divergence' => $snapshotEmployee->has_divergence,
                    'divergence_summary' => $snapshotEmployee->divergence_summary,
                    'can_generate_individual_report' => ! $snapshotEmployee->has_divergence,
                    'days' => $snapshotEmployee->days
                        ->sortBy('work_date')
                        ->map(fn ($day) => [
                            'id' => $day->id,
                            'work_date' => $day->work_date?->format('d/m/Y'),
                            'weekday_label' => $day->weekday_label,
                            'expected_schedule' => $this->buildExpectedScheduleLabel(
                                startTime: $day->expected_start_time,
                                endTime: $day->expected_end_time,
                                breakDuration: $day->expected_break_duration,
                            ),
                            'records_label' => collect($day->records ?? [])->implode(' | '),
                            'justified_minutes' => $day->justified_minutes,
                            'late_minutes' => $day->late_minutes,
                            'extra_minutes' => $day->extra_minutes,
                            'absence_minutes' => $day->absence_minutes,
                            'suspension_minutes' => $day->suspension_minutes,
                            'balance_minutes' => $day->balance_minutes,
                            'has_divergence' => $day->has_divergence,
                            'divergence_reason' => $day->divergence_reason,
                            'notes' => $day->notes,
                        ])
                        ->values(),
                ])
                ->values(),
            'availableEmployees' => $availableEmployees,
        ]);
    }

    public function destroy(Request $request, HourBankSnapshot $hourBankSnapshot): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('worktime.deleteHourBankSnapshot'), 403);
        $this->ensureSnapshotContext($request, $hourBankSnapshot);

        Audit::event('worktime.hour-bank-snapshots.deleted', $hourBankSnapshot, [
            'snapshot_id' => $hourBankSnapshot->id,
            'name' => $hourBankSnapshot->name,
            'period_start' => $hourBankSnapshot->period_start?->format('Y-m-d'),
            'period_end' => $hourBankSnapshot->period_end?->format('Y-m-d'),
        ]);

        $this->service->delete($hourBankSnapshot);

        return redirect()
            ->route('worktime.hour-bank-snapshots.index')
            ->with('alert', Flash::success(
                'Fechamento excluído',
                'O fechamento foi excluído com sucesso.'
            ));
    }

    public function addEmployees(
        AddHourBankSnapshotEmployeesRequest $request,
        HourBankSnapshot $hourBankSnapshot,
    ): RedirectResponse {
        abort_unless($request->user()->hasPermission('worktime.updateHourBankSnapshot'), 403);
        $this->ensureSnapshotContext($request, $hourBankSnapshot);

        $employeeIds = $request->validated('employee_ids');

        $this->service->addEmployees(
            snapshot: $hourBankSnapshot,
            employeeIds: $employeeIds,
            user: $request->user(),
        );

        Audit::event('worktime.hour-bank-snapshots.employees-added', $hourBankSnapshot, [
            'snapshot_id' => $hourBankSnapshot->id,
            'employee_ids' => $employeeIds,
        ]);

        return redirect()
            ->route('worktime.hour-bank-snapshots.show', $hourBankSnapshot)
            ->with('alert', Flash::success(
                'Funcionários incluídos',
                'Os funcionários foram incluídos no fechamento com sucesso.'
            ));
    }

    public function removeEmployee(
        Request $request,
        HourBankSnapshot $hourBankSnapshot,
        HourBankSnapshotEmployee $hourBankSnapshotEmployee,
    ): RedirectResponse {
        abort_unless($request->user()->hasPermission('worktime.updateHourBankSnapshot'), 403);
        $this->ensureSnapshotContext($request, $hourBankSnapshot);
        $this->ensureSnapshotEmployeeContext($hourBankSnapshot, $hourBankSnapshotEmployee);

        $this->service->removeEmployee(
            snapshot: $hourBankSnapshot,
            snapshotEmployee: $hourBankSnapshotEmployee,
        );

        Audit::event('worktime.hour-bank-snapshots.employee-removed', $hourBankSnapshot, [
            'snapshot_id' => $hourBankSnapshot->id,
            'snapshot_employee_id' => $hourBankSnapshotEmployee->id,
            'employee_id' => $hourBankSnapshotEmployee->employee_id,
        ]);

        return redirect()
            ->route('worktime.hour-bank-snapshots.show', $hourBankSnapshot)
            ->with('alert', Flash::success(
                'Funcionário removido',
                'O funcionário foi removido do fechamento com sucesso.'
            ));
    }

    public function consolidate(Request $request, HourBankSnapshot $hourBankSnapshot): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('worktime.consolidateHourBankSnapshot'), 403);
        $this->ensureSnapshotContext($request, $hourBankSnapshot);

        $this->service->consolidate(
            snapshot: $hourBankSnapshot,
            user: $request->user(),
        );

        Audit::event('worktime.hour-bank-snapshots.consolidated', $hourBankSnapshot, [
            'snapshot_id' => $hourBankSnapshot->id,
        ]);

        return redirect()
            ->route('worktime.hour-bank-snapshots.show', $hourBankSnapshot)
            ->with('alert', Flash::success(
                'Fechamento consolidado',
                'O fechamento foi consolidado com sucesso.'
            ));
    }

    public function reverse(Request $request, HourBankSnapshot $hourBankSnapshot): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('worktime.reverseHourBankSnapshot'), 403);
        $this->ensureSnapshotContext($request, $hourBankSnapshot);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->service->reverse(
            snapshot: $hourBankSnapshot,
            user: $request->user(),
            reason: $validated['reason'],
        );

        Audit::event('worktime.hour-bank-snapshots.reversed', $hourBankSnapshot, [
            'snapshot_id' => $hourBankSnapshot->id,
            'reason' => $validated['reason'],
        ]);

        return redirect()
            ->route('worktime.hour-bank-snapshots.show', $hourBankSnapshot)
            ->with('alert', Flash::success(
                'Fechamento revertido',
                'O fechamento foi revertido com sucesso.'
            ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('worktime.exportHourBankSnapshot'), 403);

        $filename = 'hour-bank-snapshots-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $snapshots = $this->filteredQuery($request)
            ->withCount('employees')
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($snapshots) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Periodo inicial',
                'Periodo final',
                'Status',
                'Funcionarios',
                'Consolidado em',
                'Revertido em',
            ], ';');

            foreach ($snapshots as $snapshot) {
                fputcsv($handle, [
                    $snapshot->id,
                    $snapshot->name,
                    $snapshot->period_start?->format('d/m/Y'),
                    $snapshot->period_end?->format('d/m/Y'),
                    $snapshot->status->label(),
                    $snapshot->employees_count,
                    $snapshot->consolidated_at?->format('d/m/Y H:i:s'),
                    $snapshot->reversed_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        abort_unless($request->user()->hasPermission('worktime.exportHourBankSnapshot'), 403);

        $snapshots = $this->filteredQuery($request)
            ->withCount('employees')
            ->latest()
            ->get()
            ->map(fn (HourBankSnapshot $snapshot) => [
                'id' => $snapshot->id,
                'name' => $snapshot->name,
                'period_start' => $snapshot->period_start?->format('d/m/Y'),
                'period_end' => $snapshot->period_end?->format('d/m/Y'),
                'status_label' => $snapshot->status->label(),
                'employees_count' => $snapshot->employees_count,
                'consolidated_at' => $snapshot->consolidated_at?->format('d/m/Y H:i:s'),
                'reversed_at' => $snapshot->reversed_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption(['isPhpEnabled' => false])
            ->loadView('pdf.hour-bank-snapshots-report', [
                'snapshots' => $snapshots,
                'generatedAt' => now()->format('d/m/Y H:i:s'),
                'companyName' => CompanyContext::current()?->name ?? 'Empresa',
            ])
            ->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="hour-bank-snapshots-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
        ]);
    }

    public function exportGeneralPreview(Request $request, HourBankSnapshot $hourBankSnapshot)
    {
        abort_unless($request->user()->hasPermission('worktime.exportHourBankSnapshot'), 403);
        $this->ensureSnapshotContext($request, $hourBankSnapshot);

        $hourBankSnapshot->load('employees');

        if ($hourBankSnapshot->employees->contains(fn (HourBankSnapshotEmployee $employee) => $employee->has_divergence)) {
            throw ValidationException::withMessages([
                'snapshot' => 'O relatório geral prévio só pode ser emitido quando não houver divergências.',
            ]);
        }

        $pdf = Pdf::setOption(['isPhpEnabled' => false])
            ->loadView('pdf.hour-bank-snapshot-general-preview', [
                'snapshot' => $hourBankSnapshot,
                'employees' => $hourBankSnapshot->employees->sortBy('employee_name')->values(),
            ])
            ->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="hour-bank-snapshot-general-preview-' . $hourBankSnapshot->id . '.pdf"',
        ]);
    }

    public function exportGeneralConsolidated(Request $request, HourBankSnapshot $hourBankSnapshot)
    {
        abort_unless($request->user()->hasPermission('worktime.exportHourBankSnapshot'), 403);
        $this->ensureSnapshotContext($request, $hourBankSnapshot);

        abort_unless($hourBankSnapshot->status->isConsolidated(), 422);

        $hourBankSnapshot->load('employees');

        $pdf = Pdf::setOption(['isPhpEnabled' => false])
            ->loadView('pdf.hour-bank-snapshot-general-consolidated', [
                'snapshot' => $hourBankSnapshot,
                'employees' => $hourBankSnapshot->employees->sortBy('employee_name')->values(),
            ])
            ->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="hour-bank-snapshot-general-consolidated-' . $hourBankSnapshot->id . '.pdf"',
        ]);
    }

    public function exportEmployeeReport(
        Request $request,
        HourBankSnapshot $hourBankSnapshot,
        HourBankSnapshotEmployee $hourBankSnapshotEmployee,
    ) {
        abort_unless($request->user()->hasPermission('worktime.exportHourBankSnapshot'), 403);
        $this->ensureSnapshotContext($request, $hourBankSnapshot);
        $this->ensureSnapshotEmployeeContext($hourBankSnapshot, $hourBankSnapshotEmployee);

        if ($hourBankSnapshotEmployee->has_divergence) {
            throw ValidationException::withMessages([
                'snapshot_employee' => 'O relatório individual só pode ser emitido quando o funcionário não possuir divergências.',
            ]);
        }

        $hourBankSnapshotEmployee->load('days');

        $pdf = Pdf::setOption(['isPhpEnabled' => false])
            ->loadView('pdf.hour-bank-snapshot-employee', [
                'snapshot' => $hourBankSnapshot,
                'employee' => $hourBankSnapshotEmployee,
                'days' => $hourBankSnapshotEmployee->days->sortBy('work_date')->values(),
            ])
            ->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="hour-bank-snapshot-employee-' . $hourBankSnapshotEmployee->id . '.pdf"',
        ]);
    }

    protected function filteredQuery(Request $request)
    {
        return HourBankSnapshot::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('company_id', CompanyContext::id());
    }

    protected function ensureSnapshotContext(Request $request, HourBankSnapshot $hourBankSnapshot): void
    {
        abort_unless(
            $hourBankSnapshot->tenant_id === $request->user()->tenant_id
            && $hourBankSnapshot->company_id === CompanyContext::id(),
            404
        );
    }

    protected function ensureSnapshotEmployeeContext(
        HourBankSnapshot $hourBankSnapshot,
        HourBankSnapshotEmployee $hourBankSnapshotEmployee,
    ): void {
        abort_unless(
            (int) $hourBankSnapshotEmployee->hour_bank_snapshot_id === (int) $hourBankSnapshot->id,
            404
        );
    }

    protected function buildExpectedScheduleLabel(
        ?string $startTime,
        ?string $endTime,
        ?string $breakDuration,
    ): string {
        $parts = [];

        if (filled($startTime) && filled($endTime)) {
            $parts[] = $this->normalizeTimeForLabel($startTime) . ' - ' . $this->normalizeTimeForLabel($endTime);
        }

        if (filled($breakDuration)) {
            $parts[] = 'Int. ' . $this->normalizeTimeForLabel($breakDuration);
        }

        if (empty($parts)) {
            return '—';
        }

        return implode(' | ', $parts);
    }

    protected function normalizeTimeForLabel(string $time): string
    {
        return substr($time, 0, 5);
    }
}
