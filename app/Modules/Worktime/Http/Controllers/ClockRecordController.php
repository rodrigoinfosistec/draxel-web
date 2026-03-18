<?php

namespace App\Modules\Worktime\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Modules\Worktime\Enums\ClockRecordSourceType;
use App\Modules\Worktime\Http\Requests\StoreManualClockRecordsRequest;
use App\Modules\Worktime\Http\Requests\UpdateManualClockRecordRequest;
use App\Modules\Worktime\Models\ClockRecord;
use App\Modules\Worktime\Services\ClockRecordService;
use App\Support\Audit\Audit;
use App\Support\CompanyContext;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClockRecordController extends Controller
{
    public function __construct(
        protected ClockRecordService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ClockRecord::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = $request->string('search')->toString();
        $date = $request->string('date')->toString();

        $records = ClockRecord::query()
            ->with('employee:id,name')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search, fn ($query) => $query->whereHas('employee', function ($subQuery) use ($search) {
                $subQuery->where('name', 'ilike', "%{$search}%");
            }))
            ->when($date, fn ($query) => $query->whereDate('recorded_at', $date))
            ->orderBy('recorded_at')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (ClockRecord $record) => [
                'id' => $record->id,
                'employee_id' => $record->employee_id,
                'employee_name' => $record->employee?->name,
                'source_type' => $record->source_type?->value,
                'source_type_label' => $record->source_type?->label(),
                'recorded_at' => $record->recorded_at?->format('d/m/Y H:i'),
                'date' => $record->recorded_at?->format('Y-m-d'),
                'date_label' => $record->recorded_at?->format('d/m/Y'),
                'time' => $record->recorded_at?->format('H:i'),
                'notes' => $record->notes,
            ]);

        return Inertia::render('worktime/clock-records/Index', [
            'records' => $records,
            'filters' => [
                'search' => $search,
                'date' => $date,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', ClockRecord::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = $request->string('search')->toString();
        $date = $request->string('date')->toString();
        $filename = 'clock-records-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $records = ClockRecord::query()
            ->with('employee:id,name')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search, fn ($query) => $query->whereHas('employee', function ($subQuery) use ($search) {
                $subQuery->where('name', 'ilike', "%{$search}%");
            }))
            ->when($date, fn ($query) => $query->whereDate('recorded_at', $date))
            ->orderBy('recorded_at')
            ->get();

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Funcionário',
                'Origem',
                'Data',
                'Hora',
                'Observações',
                'Criado em',
            ], ';');

            foreach ($records as $record) {
                fputcsv($handle, [
                    $record->id,
                    $record->employee?->name,
                    $record->source_type?->label(),
                    $record->recorded_at?->format('d/m/Y'),
                    $record->recorded_at?->format('H:i:s'),
                    $record->notes,
                    $record->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', ClockRecord::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = $request->string('search')->toString();
        $date = $request->string('date')->toString();

        $records = ClockRecord::query()
            ->with('employee:id,name')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search, fn ($query) => $query->whereHas('employee', function ($subQuery) use ($search) {
                $subQuery->where('name', 'ilike', "%{$search}%");
            }))
            ->when($date, fn ($query) => $query->whereDate('recorded_at', $date))
            ->orderBy('recorded_at')
            ->get()
            ->map(fn (ClockRecord $record) => [
                'id' => $record->id,
                'employee_name' => $record->employee?->name,
                'source_type' => $record->source_type?->label(),
                'date' => $record->recorded_at?->format('d/m/Y'),
                'time' => $record->recorded_at?->format('H:i:s'),
                'notes' => $record->notes,
                'created_at' => $record->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.clock-records-report', [
                'records' => $records,
                'filters' => [
                    'search' => $search,
                    'date' => $date,
                ],
                'generatedAt' => now()->format('d/m/Y H:i:s'),
                'tenantName' => $request->user()->tenant?->name ?? 'Tenant',
                'companyName' => CompanyContext::current()?->name ?? 'Empresa',
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
                'Content-Disposition' => 'attachment; filename="clock-records-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ClockRecord::class);

        return Inertia::render('worktime/clock-records/Create', [
            'employees' => $this->employees($request),
        ]);
    }

    public function store(StoreManualClockRecordsRequest $request): RedirectResponse
    {
        $records = $this->service->createManualBatch(
            data: $request->validated(),
            user: $request->user(),
        );

        Audit::event('worktime.clock-records.created', null, [
            'employee_id' => $request->validated('employee_id'),
            'date' => $request->validated('date'),
            'records_count' => $records->count(),
            'source_type' => ClockRecordSourceType::Manual->value,
        ]);

        return redirect()
            ->route('worktime.clock-records.index')
            ->with('alert', Flash::success('Registros criados', 'Os registros de ponto foram salvos com sucesso.'));
    }

    public function edit(Request $request, ClockRecord $clockRecord): Response
    {
        $this->authorize('update', $clockRecord);

        return Inertia::render('worktime/clock-records/Edit', [
            'record' => [
                'id' => $clockRecord->id,
                'employee_id' => $clockRecord->employee_id,
                'date' => $clockRecord->recorded_at?->format('Y-m-d'),
                'time' => $clockRecord->recorded_at?->format('H:i'),
                'notes' => $clockRecord->notes,
            ],
            'employees' => $this->employees($request),
        ]);
    }

    public function update(UpdateManualClockRecordRequest $request, ClockRecord $clockRecord): RedirectResponse
    {
        $before = [
            'employee_id' => $clockRecord->employee_id,
            'recorded_at' => $clockRecord->recorded_at?->format('Y-m-d H:i:s'),
            'notes' => $clockRecord->notes,
        ];

        $clockRecord = $this->service->updateManual(
            clockRecord: $clockRecord,
            data: $request->validated(),
            user: $request->user(),
        );

        Audit::event('worktime.clock-records.updated', $clockRecord, [
            'before' => $before,
            'after' => [
                'employee_id' => $clockRecord->employee_id,
                'recorded_at' => $clockRecord->recorded_at?->format('Y-m-d H:i:s'),
                'notes' => $clockRecord->notes,
            ],
        ]);

        return redirect()
            ->route('worktime.clock-records.index')
            ->with('alert', Flash::success('Registro atualizado', 'O registro de ponto foi atualizado com sucesso.'));
    }

    public function destroy(ClockRecord $clockRecord): RedirectResponse
    {
        $this->authorize('delete', $clockRecord);

        $snapshot = [
            'employee_id' => $clockRecord->employee_id,
            'recorded_at' => $clockRecord->recorded_at?->format('Y-m-d H:i:s'),
            'notes' => $clockRecord->notes,
        ];

        Audit::event('worktime.clock-records.deleted', $clockRecord, $snapshot);

        $clockRecord->delete();

        return redirect()
            ->route('worktime.clock-records.index')
            ->with('alert', Flash::success('Registro removido', 'O registro de ponto foi removido com sucesso.'));
    }

    protected function employees(Request $request)
    {
        return Employee::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('company_id', session('current_company_id'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
            ]);
    }
}
