<?php

namespace App\Modules\Worktime\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Modules\Worktime\Enums\EmployeeEventType;
use App\Modules\Worktime\Http\Requests\StoreEmployeeEventRequest;
use App\Modules\Worktime\Http\Requests\UpdateEmployeeEventRequest;
use App\Modules\Worktime\Models\EmployeeEvent;
use App\Modules\Worktime\Services\EmployeeEventService;
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

class EmployeeEventController extends Controller
{
    public function __construct(
        protected EmployeeEventService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmployeeEvent::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = trim((string) $request->string('search')->value());

        $events = EmployeeEvent::query()
            ->with('employee:id,name')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->whereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhere('event_type', 'ilike', "%{$search}%");
                });
            })
            ->latest('starts_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (EmployeeEvent $event) => [
                'id' => $event->id,
                'employee_name' => $event->employee?->name,
                'event_type' => $event->event_type?->value,
                'event_type_label' => $event->event_type?->label(),
                'starts_at' => $event->starts_at?->format('d/m/Y H:i'),
                'ends_at' => $event->ends_at?->format('d/m/Y H:i'),
                'notes' => $event->notes,
            ]);

        return Inertia::render('worktime/employee-events/Index', [
            'events' => $events,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', EmployeeEvent::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = trim((string) $request->string('search')->value());
        $filename = 'employee-events-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $events = EmployeeEvent::query()
            ->with('employee:id,name')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->whereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhere('event_type', 'ilike', "%{$search}%");
                });
            })
            ->latest('starts_at')
            ->get();

        return response()->streamDownload(function () use ($events) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Funcionário',
                'Tipo',
                'Início',
                'Fim',
                'Observações',
                'Criado em',
            ], ';');

            foreach ($events as $event) {
                fputcsv($handle, [
                    $event->id,
                    $event->employee?->name,
                    $event->event_type?->label(),
                    $event->starts_at?->format('d/m/Y H:i:s'),
                    $event->ends_at?->format('d/m/Y H:i:s'),
                    $event->notes,
                    $event->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', EmployeeEvent::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = trim((string) $request->string('search')->value());

        $events = EmployeeEvent::query()
            ->with('employee:id,name')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->whereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhere('event_type', 'ilike', "%{$search}%");
                });
            })
            ->latest('starts_at')
            ->get()
            ->map(fn (EmployeeEvent $event) => [
                'id' => $event->id,
                'employee_name' => $event->employee?->name,
                'event_type' => $event->event_type?->label(),
                'starts_at' => $event->starts_at?->format('d/m/Y H:i:s'),
                'ends_at' => $event->ends_at?->format('d/m/Y H:i:s'),
                'notes' => $event->notes,
                'created_at' => $event->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.employee-events-report', [
                'events' => $events,
                'filters' => [
                    'search' => $search,
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
                'Content-Disposition' => 'attachment; filename="employee-events-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', EmployeeEvent::class);

        return Inertia::render('worktime/employee-events/Create', $this->formData($request));
    }

    public function store(StoreEmployeeEventRequest $request): RedirectResponse
    {
        $employeeEvent = $this->service->create(
            data: $request->validated(),
            user: $request->user(),
        );

        Audit::event('worktime.employee-events.created', $employeeEvent, [
            'employee_id' => $employeeEvent->employee_id,
            'event_type' => $employeeEvent->event_type?->value,
            'starts_at' => $employeeEvent->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $employeeEvent->ends_at?->format('Y-m-d H:i:s'),
            'notes' => $employeeEvent->notes,
        ]);

        return redirect()
            ->route('worktime.employee-events.index')
            ->with('alert', Flash::success('Evento criado', 'O evento foi criado com sucesso.'));
    }

    public function edit(Request $request, EmployeeEvent $employeeEvent): Response
    {
        $this->authorize('update', $employeeEvent);

        return Inertia::render('worktime/employee-events/Edit', array_merge(
            $this->formData($request),
            [
                'event' => [
                    'id' => $employeeEvent->id,
                    'employee_id' => $employeeEvent->employee_id,
                    'event_type' => $employeeEvent->event_type?->value,
                    'input_mode' => in_array($employeeEvent->event_type, [
                        EmployeeEventType::Absence,
                        EmployeeEventType::Suspension,
                    ], true)
                        ? 'schedule_day'
                        : $this->resolveInputMode($employeeEvent),
                    'date' => $employeeEvent->starts_at?->format('Y-m-d'),
                    'starts_at' => $employeeEvent->starts_at?->format('Y-m-d\TH:i'),
                    'ends_at' => $employeeEvent->ends_at?->format('Y-m-d\TH:i'),
                    'notes' => $employeeEvent->notes,
                ],
            ]
        ));
    }

    public function update(UpdateEmployeeEventRequest $request, EmployeeEvent $employeeEvent): RedirectResponse
    {
        $before = [
            'employee_id' => $employeeEvent->employee_id,
            'event_type' => $employeeEvent->event_type?->value,
            'starts_at' => $employeeEvent->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $employeeEvent->ends_at?->format('Y-m-d H:i:s'),
            'notes' => $employeeEvent->notes,
        ];

        $employeeEvent = $this->service->update(
            employeeEvent: $employeeEvent,
            data: $request->validated(),
            user: $request->user(),
        );

        Audit::event('worktime.employee-events.updated', $employeeEvent, [
            'before' => $before,
            'after' => [
                'employee_id' => $employeeEvent->employee_id,
                'event_type' => $employeeEvent->event_type?->value,
                'starts_at' => $employeeEvent->starts_at?->format('Y-m-d H:i:s'),
                'ends_at' => $employeeEvent->ends_at?->format('Y-m-d H:i:s'),
                'notes' => $employeeEvent->notes,
            ],
        ]);

        return redirect()
            ->route('worktime.employee-events.index')
            ->with('alert', Flash::success('Evento atualizado', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(EmployeeEvent $employeeEvent): RedirectResponse
    {
        $this->authorize('delete', $employeeEvent);

        $snapshot = [
            'employee_id' => $employeeEvent->employee_id,
            'event_type' => $employeeEvent->event_type?->value,
            'starts_at' => $employeeEvent->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $employeeEvent->ends_at?->format('Y-m-d H:i:s'),
            'notes' => $employeeEvent->notes,
        ];

        Audit::event('worktime.employee-events.deleted', $employeeEvent, $snapshot);

        $this->service->delete($employeeEvent);

        return redirect()
            ->route('worktime.employee-events.index')
            ->with('alert', Flash::success('Evento removido', 'O evento foi removido com sucesso.'));
    }

    protected function formData(Request $request): array
    {
        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        $employees = Employee::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
            ]);

        return [
            'employees' => $employees,
            'eventTypes' => EmployeeEventType::formOptions(),
        ];
    }

    protected function resolveInputMode(EmployeeEvent $employeeEvent): string
    {
        if (! $employeeEvent->starts_at || ! $employeeEvent->ends_at) {
            return 'custom_period';
        }

        if ($employeeEvent->starts_at->toDateString() !== $employeeEvent->ends_at->toDateString()) {
            return 'custom_period';
        }

        $weekdayKey = strtolower($employeeEvent->starts_at->englishDayOfWeek);

        $employeeTime = DB::table('employee_times')
            ->where('tenant_id', $employeeEvent->tenant_id)
            ->where('company_id', $employeeEvent->company_id)
            ->where('employee_id', $employeeEvent->employee_id)
            ->where('weekday', $weekdayKey)
            ->first([
                'start_time',
                'end_time',
            ]);

        if (! $employeeTime || ! $employeeTime->start_time || ! $employeeTime->end_time) {
            return 'custom_period';
        }

        $expectedStart = $employeeEvent->starts_at->format('H:i:s') === $employeeTime->start_time
            || $employeeEvent->starts_at->format('H:i:s') === $employeeTime->start_time . ':00';

        $expectedEnd = $employeeEvent->ends_at->format('H:i:s') === $employeeTime->end_time
            || $employeeEvent->ends_at->format('H:i:s') === $employeeTime->end_time . ':00';

        return $expectedStart && $expectedEnd
            ? 'schedule_day'
            : 'custom_period';
    }
}
