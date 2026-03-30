<?php

namespace App\Modules\Worktime\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Worktime\Http\Requests\StoreClockRecordImportRequest;
use App\Modules\Worktime\Models\ClockRecordImport;
use App\Modules\Worktime\Models\ClockRecordImportItem;
use App\Modules\Worktime\Models\TenantClockDevice;
use App\Modules\Worktime\Services\ClockRecordImportService;
use App\Support\Audit\Audit;
use App\Support\CompanyContext;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClockRecordImportController extends Controller
{
    public function __construct(
        protected ClockRecordImportService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('worktime.viewAnyClockRecordImport'), 403);

        $search = trim((string) $request->string('search')->value());

        $imports = ClockRecordImport::query()
            ->with('tenantClockDevice.clockDevice')
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('company_id', session('current_company_id'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('original_filename', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('tenantClockDevice.clockDevice', function ($deviceQuery) use ($search) {
                            $deviceQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (ClockRecordImport $import) {
                $canRevert = $import->status?->value === 'launched'
                    && $this->service->canRevert($import);

                return [
                    'id' => $import->id,
                    'original_filename' => $import->original_filename,
                    'status' => $import->status?->value,
                    'status_label' => $import->status?->label(),
                    'device_name' => $import->tenantClockDevice?->clockDevice?->name,
                    'total_items' => $import->total_items,
                    'valid_items' => $import->valid_items,
                    'invalid_items' => $import->invalid_items,
                    'created_at' => $import->created_at?->format('d/m/Y H:i'),
                    'can_delete' => $import->status?->value !== 'launched',
                    'can_revert' => $canRevert,
                    'can_revert_reason' => $canRevert
                        ? null
                        : ($import->status?->value === 'launched'
                            ? $this->service->getCannotRevertReason($import)
                            : null),
                ];
            });

        return Inertia::render('worktime/clock-record-imports/Index', [
            'imports' => $imports,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('worktime.exportClockRecordImport'), 403);

        $imports = ClockRecordImport::query()
            ->with('tenantClockDevice.clockDevice')
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('company_id', session('current_company_id'))
            ->latest()
            ->get();

        $filename = 'clock-record-imports-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($imports) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Arquivo',
                'Device',
                'Status',
                'Total de itens',
                'Itens válidos',
                'Itens divergentes',
                'Criado em',
            ], ';');

            foreach ($imports as $import) {
                fputcsv($handle, [
                    $import->id,
                    $import->original_filename,
                    $import->tenantClockDevice?->clockDevice?->name,
                    $import->status?->label(),
                    $import->total_items,
                    $import->valid_items,
                    $import->invalid_items,
                    $import->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        abort_unless($request->user()->hasPermission('worktime.exportClockRecordImport'), 403);

        $imports = ClockRecordImport::query()
            ->with('tenantClockDevice.clockDevice')
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('company_id', session('current_company_id'))
            ->latest()
            ->get()
            ->map(fn (ClockRecordImport $import) => [
                'id' => $import->id,
                'original_filename' => $import->original_filename,
                'device_name' => $import->tenantClockDevice?->clockDevice?->name,
                'status' => $import->status?->label(),
                'total_items' => $import->total_items,
                'valid_items' => $import->valid_items,
                'invalid_items' => $import->invalid_items,
                'created_at' => $import->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
            'isPhpEnabled' => false,
        ])
            ->loadView('pdf.clock-record-imports-report', [
                'imports' => $imports,
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
                'Content-Disposition' => 'attachment; filename="clock-record-imports-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('worktime.createClockRecordImport'), 403);

        $devices = TenantClockDevice::query()
            ->with('clockDevice')
            ->where('tenant_id', $request->user()->tenant_id)
            ->get()
            ->map(fn ($device) => [
                'id' => $device->id,
                'name' => $device->clockDevice?->name,
            ]);

        return Inertia::render('worktime/clock-record-imports/Create', [
            'devices' => $devices,
        ]);
    }

    public function store(StoreClockRecordImportRequest $request): RedirectResponse
    {
        $import = $this->service->createFromUpload(
            data: $request->validated(),
            user: $request->user(),
        );

        Audit::event('worktime.clock-record-imports.created', $import, [
            'original_filename' => $import->original_filename,
            'status' => $import->status?->value,
            'total_items' => $import->total_items,
            'valid_items' => $import->valid_items,
            'invalid_items' => $import->invalid_items,
        ]);

        return redirect()
            ->route('worktime.clock-record-imports.show', $import)
            ->with('alert', Flash::success('Importação criada', 'O arquivo foi processado com sucesso.'));
    }

    public function show(Request $request, ClockRecordImport $clockRecordImport): Response
    {
        abort_unless($request->user()->hasPermission('worktime.viewClockRecordImport'), 403);
        abort_unless(
            $clockRecordImport->tenant_id === $request->user()->tenant_id
            && $clockRecordImport->company_id === session('current_company_id'),
            404
        );

        $clockRecordImport->load(['items.employee', 'tenantClockDevice.clockDevice']);

        $employees = DB::table('employees')
            ->where('tenant_id', $clockRecordImport->tenant_id)
            ->where('company_id', $clockRecordImport->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'registration'])
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'registration' => $employee->registration,
                'label' => trim($employee->name . ' - ' . $employee->registration),
            ])
            ->values();

        $groups = $clockRecordImport->items
            ->sortBy('recorded_at')
            ->groupBy(function ($item) {
                $employeeKey = $item->employee?->name ?? $item->employee_code ?? 'Sem funcionário';
                $dateKey = $item->recorded_at?->format('d/m/Y') ?? 'Sem data';

                return $employeeKey . '|' . $dateKey;
            })
            ->map(function ($items, $groupKey) {
                [$employeeName, $dateLabel] = explode('|', $groupKey);

                $firstItemWithEmployeeAndDate = $items->first(fn ($item) => $item->employee_id && $item->recorded_at);

                return [
                    'employee_name' => $employeeName,
                    'date_label' => $dateLabel,
                    'employee_id' => $firstItemWithEmployeeAndDate?->employee_id,
                    'date_key' => $firstItemWithEmployeeAndDate?->recorded_at?->format('Y-m-d'),
                    'times' => $items
                        ->filter(fn ($item) => $item->recorded_at)
                        ->sortBy('recorded_at')
                        ->map(fn ($item) => $item->recorded_at->format('H:i'))
                        ->values(),
                    'can_adjust_times' => filled($firstItemWithEmployeeAndDate?->employee_id)
                        && filled($firstItemWithEmployeeAndDate?->recorded_at),
                    'items' => $items
                        ->sortBy('recorded_at')
                        ->map(function ($item) {
                            $payload = is_array($item->payload) ? $item->payload : [];

                            return [
                                'id' => $item->id,
                                'line_number' => $item->line_number,
                                'line_number_label' => ! empty($payload['manual_adjustment']) ? 'Manual' : (string) $item->line_number,
                                'employee_code' => $item->employee_code,
                                'employee_name' => $item->employee?->name,
                                'recorded_at' => $item->recorded_at?->format('d/m/Y H:i'),
                                'time' => $item->recorded_at?->format('H:i'),
                                'status' => $item->status?->value,
                                'status_label' => $item->status?->label(),
                                'divergence_reason' => $item->divergence_reason,
                                'raw_line' => $item->raw_line,
                                'original_raw_line' => $payload['original_raw_line'] ?? null,
                                'is_manual_adjustment' => (bool) ($payload['manual_adjustment'] ?? false),
                                'can_ignore' => $item->status?->value === 'invalid',
                                'can_resolve_employee' => $item->status?->value === 'invalid'
                                    && $item->divergence_reason === 'Funcionário não encontrado.',
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return Inertia::render('worktime/clock-record-imports/Show', [
            'import' => [
                'id' => $clockRecordImport->id,
                'original_filename' => $clockRecordImport->original_filename,
                'status' => $clockRecordImport->status?->value,
                'status_label' => $clockRecordImport->status?->label(),
                'device_name' => $clockRecordImport->tenantClockDevice?->clockDevice?->name,
                'total_items' => $clockRecordImport->total_items,
                'valid_items' => $clockRecordImport->valid_items,
                'invalid_items' => $clockRecordImport->invalid_items,
                'can_launch' => $clockRecordImport->status?->value === 'ready_to_launch',
                'can_revert' => $clockRecordImport->status?->value === 'launched'
                    && $this->service->canRevert($clockRecordImport),
                'cannot_revert_reason' => $clockRecordImport->status?->value === 'launched'
                    ? $this->service->getCannotRevertReason($clockRecordImport)
                    : null,
            ],
            'groups' => $groups,
            'employees' => $employees,
        ]);
    }

    public function destroy(Request $request, ClockRecordImport $clockRecordImport): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('worktime.deleteClockRecordImport'), 403);
        abort_unless(
            $clockRecordImport->tenant_id === $request->user()->tenant_id
            && $clockRecordImport->company_id === session('current_company_id'),
            404
        );

        Audit::event('worktime.clock-record-imports.deleted', $clockRecordImport, [
            'clock_record_import_id' => $clockRecordImport->id,
            'original_filename' => $clockRecordImport->original_filename,
            'status' => $clockRecordImport->status?->value,
        ]);

        $this->service->delete($clockRecordImport, $request->user());

        return redirect()
            ->route('worktime.clock-record-imports.index')
            ->with('alert', Flash::success('Importação excluída', 'A importação foi excluída com sucesso.'));
    }

    public function ignoreItem(
        Request $request,
        ClockRecordImport $clockRecordImport,
        ClockRecordImportItem $clockRecordImportItem,
    ): RedirectResponse {
        abort_unless($request->user()->hasPermission('worktime.reverseClockRecordImport'), 403);
        abort_unless(
            $clockRecordImport->tenant_id === $request->user()->tenant_id
            && $clockRecordImport->company_id === session('current_company_id'),
            404
        );

        $clockRecordImport = $this->service->ignoreItem(
            import: $clockRecordImport,
            item: $clockRecordImportItem,
            user: $request->user(),
        );

        Audit::event('worktime.clock-record-import-items.ignored', $clockRecordImport, [
            'clock_record_import_id' => $clockRecordImport->id,
            'clock_record_import_item_id' => $clockRecordImportItem->id,
        ]);

        return redirect()
            ->route('worktime.clock-record-imports.show', $clockRecordImport)
            ->with('alert', Flash::success('Item desconsiderado', 'O item foi desconsiderado e a importação foi recalculada.'));
    }

    public function resolveEmployee(
        Request $request,
        ClockRecordImport $clockRecordImport,
        ClockRecordImportItem $clockRecordImportItem,
    ): RedirectResponse {
        abort_unless($request->user()->hasPermission('worktime.updateClockRecordImport'), 403);
        abort_unless(
            $clockRecordImport->tenant_id === $request->user()->tenant_id
            && $clockRecordImport->company_id === session('current_company_id'),
            404
        );

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
        ]);

        $clockRecordImport = $this->service->resolveEmployeeForItem(
            import: $clockRecordImport,
            item: $clockRecordImportItem,
            employeeId: (int) $validated['employee_id'],
            user: $request->user(),
        );

        Audit::event('worktime.clock-record-import-items.employee-resolved', $clockRecordImport, [
            'clock_record_import_id' => $clockRecordImport->id,
            'clock_record_import_item_id' => $clockRecordImportItem->id,
            'employee_id' => (int) $validated['employee_id'],
        ]);

        return redirect()
            ->route('worktime.clock-record-imports.show', $clockRecordImport)
            ->with('alert', Flash::success('Funcionário vinculado', 'O item foi corrigido e a importação foi recalculada.'));
    }

    public function adjustTimes(Request $request, ClockRecordImport $clockRecordImport): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('worktime.updateClockRecordImport'), 403);
        abort_unless(
            $clockRecordImport->tenant_id === $request->user()->tenant_id
            && $clockRecordImport->company_id === session('current_company_id'),
            404
        );

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'times' => ['required', 'array', 'min:1'],
            'times.*' => ['required', 'date_format:H:i'],
        ]);

        $clockRecordImport = $this->service->adjustGroupTimes(
            import: $clockRecordImport,
            employeeId: (int) $validated['employee_id'],
            date: $validated['date'],
            times: $validated['times'],
            user: $request->user(),
        );

        Audit::event('worktime.clock-record-import-groups.times-adjusted', $clockRecordImport, [
            'clock_record_import_id' => $clockRecordImport->id,
            'employee_id' => (int) $validated['employee_id'],
            'date' => $validated['date'],
            'times' => $validated['times'],
        ]);

        return redirect()
            ->route('worktime.clock-record-imports.show', $clockRecordImport)
            ->with('alert', Flash::success('Horários ajustados', 'Os horários do dia foram ajustados e a importação foi recalculada.'));
    }

    public function launch(Request $request, ClockRecordImport $clockRecordImport): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('worktime.launchClockRecordImport'), 403);
        abort_unless(
            $clockRecordImport->tenant_id === $request->user()->tenant_id
            && $clockRecordImport->company_id === session('current_company_id'),
            404
        );

        $clockRecordImport = $this->service->launch($clockRecordImport, $request->user());

        Audit::event('worktime.clock-record-imports.launched', $clockRecordImport, [
            'status' => $clockRecordImport->status?->value,
            'launched_at' => $clockRecordImport->launched_at?->format('Y-m-d H:i:s'),
        ]);

        return redirect()
            ->route('worktime.clock-record-imports.show', $clockRecordImport)
            ->with('alert', Flash::success('Importação lançada', 'Os registros foram lançados com sucesso.'));
    }
    public function revert(Request $request, ClockRecordImport $clockRecordImport): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('worktime.updateClockRecordImport'), 403);
        abort_unless(
            $clockRecordImport->tenant_id === $request->user()->tenant_id
            && $clockRecordImport->company_id === session('current_company_id'),
            404
        );

        $clockRecordImport = $this->service->revert($clockRecordImport, $request->user());

        Audit::event('worktime.clock-record-imports.reverted', $clockRecordImport, [
            'clock_record_import_id' => $clockRecordImport->id,
            'status' => $clockRecordImport->status?->value,
            'launched_at' => $clockRecordImport->launched_at?->format('Y-m-d H:i:s'),
        ]);

        return redirect()
            ->route('worktime.clock-record-imports.show', $clockRecordImport)
            ->with('alert', Flash::success('Importação revertida', 'Os registros lançados pela importação foram revertidos com sucesso.'));
    }
}
