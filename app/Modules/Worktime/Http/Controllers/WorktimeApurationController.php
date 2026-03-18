<?php

namespace App\Modules\Worktime\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Support\CompanyContext;
use App\Modules\Worktime\Services\WorktimeApurationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorktimeApurationController extends Controller
{
    public function __construct(
        protected WorktimeApurationService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('worktime.viewAnyApuration'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer'],
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? now()->endOfMonth()->format('Y-m-d');
        $employeeIds = $validated['employee_ids'] ?? [];

        $apuration = $this->service->calculate(
            tenantId: $request->user()->tenant_id,
            companyId: $company->id,
            startDate: $startDate,
            endDate: $endDate,
            employeeIds: $employeeIds,
        );

        $employees = Employee::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
            ]);

        return Inertia::render('worktime/apurations/Index', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'employee_ids' => $employeeIds,
            ],
            'employees' => $employees,
            'apuration' => $apuration,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('worktime.exportApuration'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer'],
        ]);

        $apuration = $this->service->calculate(
            tenantId: $request->user()->tenant_id,
            companyId: $company->id,
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            employeeIds: $validated['employee_ids'] ?? [],
        );

        $filename = 'worktime-apuration-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($apuration) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Funcionário',
                'Data',
                'Jornada prevista (min)',
                'Trabalhado (min)',
                'Atraso (min)',
                'Saída antecipada (min)',
                'Extra (min)',
                'Ausência (min)',
                'Registros',
                'Status',
                'Observações',
            ], ';');

            foreach ($apuration['flat_days'] as $day) {
                fputcsv($handle, [
                    $day['employee_name'],
                    $day['date'],
                    $day['expected_minutes'],
                    $day['worked_minutes'],
                    $day['delay_minutes'],
                    $day['early_exit_minutes'],
                    $day['overtime_minutes'],
                    $day['absence_minutes'],
                    $day['records_count'],
                    $day['status'],
                    $day['notes'],
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        abort_unless($request->user()->hasPermission('worktime.exportApuration'), 403);

        $company = CompanyContext::current();

        abort_unless($company, 404);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer'],
        ]);

        $apuration = $this->service->calculate(
            tenantId: $request->user()->tenant_id,
            companyId: $company->id,
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            employeeIds: $validated['employee_ids'] ?? [],
        );

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.worktime-apurations-report', [
                'days' => $apuration['flat_days'],
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
                'Content-Disposition' => 'attachment; filename="worktime-apuration-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }
}
