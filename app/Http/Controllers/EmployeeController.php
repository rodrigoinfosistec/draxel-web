<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
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

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = CompanyContext::current()?->id;
        $search = $request->string('search')->toString();

        $employees = Employee::query()
            ->with([
                'department:id,name',
                'position:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('cpf', 'ilike', "%{$search}%")
                    ->orWhere('registration', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'cpf' => $employee->cpf,
                'registration' => $employee->registration,
                'is_active' => $employee->is_active,
                'department' => $employee->department
                    ? [
                        'id' => $employee->department->id,
                        'name' => $employee->department->name,
                    ]
                    : null,
                'position' => $employee->position
                    ? [
                        'id' => $employee->position->id,
                        'name' => $employee->position->name,
                    ]
                    : null,
            ]);

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Employee::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = CompanyContext::current()?->id;
        $search = $request->string('search')->toString();
        $filename = 'employees-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $employees = Employee::query()
            ->with([
                'department:id,name',
                'position:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('cpf', 'ilike', "%{$search}%")
                    ->orWhere('registration', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($employees) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'CPF',
                'Matrícula',
                'Departamento',
                'Cargo',
                'Status',
                'Criado em',
            ], ';');

            foreach ($employees as $employee) {
                fputcsv($handle, [
                    $employee->id,
                    $employee->name,
                    $this->formatCpf($employee->cpf),
                    $employee->registration,
                    $employee->department?->name,
                    $employee->position?->name,
                    $employee->is_active ? 'Ativo' : 'Inativo',
                    $employee->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Employee::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = CompanyContext::current()?->id;
        $search = $request->string('search')->toString();

        $employees = Employee::query()
            ->with([
                'department:id,name',
                'position:id,name',
            ])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('cpf', 'ilike', "%{$search}%")
                    ->orWhere('registration', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'cpf' => $this->formatCpf($employee->cpf),
                'registration' => $employee->registration,
                'department' => $employee->department?->name,
                'position' => $employee->position?->name,
                'status' => $employee->is_active ? 'Ativo' : 'Inativo',
                'created_at' => $employee->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.employees-report', [
                'employees' => $employees,
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
                'Content-Disposition' => 'attachment; filename="employees-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Employee::class);

        return Inertia::render('employees/Create', $this->formData($request));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;
        $companyId = CompanyContext::current()?->id;

        DB::transaction(function () use ($request, $tenantId, $companyId) {
            $data = $request->validated();

            $employee = Employee::create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'department_id' => $data['department_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'name' => $data['name'],
                'cpf' => $data['cpf'],
                'registration' => $data['registration'],
                'is_active' => $data['is_active'],
            ]);

            Audit::event('employees.created', $employee, [
                'name' => $employee->name,
                'cpf' => $employee->cpf,
                'registration' => $employee->registration,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id,
                'is_active' => $employee->is_active,
            ]);
        });

        return redirect()
            ->route('employees.index')
            ->with('alert', Flash::success('Funcionário criado', 'O funcionário foi criado com sucesso.'));
    }

    public function edit(Request $request, Employee $employee): Response
    {
        $this->authorize('update', $employee);

        return Inertia::render('employees/Edit', array_merge(
            $this->editFormData($request),
            [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'cpf' => $employee->cpf,
                    'registration' => $employee->registration,
                    'department_id' => $employee->department_id,
                    'position_id' => $employee->position_id,
                    'company_alias_id' => $employee->company_alias_id,
                    'is_active' => $employee->is_active,
                ],
            ]
        ));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($request, $employee) {
            $data = $request->validated();

            $before = [
                'name' => $employee->name,
                'cpf' => $employee->cpf,
                'registration' => $employee->registration,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id,
                'company_alias_id' => $employee->company_alias_id,
                'is_active' => $employee->is_active,
            ];

            $employee->update([
                'department_id' => $data['department_id'] ?? null,
                'position_id' => $data['position_id'] ?? null,
                'company_alias_id' => $data['company_alias_id'] ?? null,
                'name' => $data['name'],
                'cpf' => $data['cpf'],
                'registration' => $data['registration'],
                'is_active' => $data['is_active'],
            ]);

            Audit::event('employees.updated', $employee, [
                'before' => $before,
                'after' => [
                    'name' => $employee->name,
                    'cpf' => $employee->cpf,
                    'registration' => $employee->registration,
                    'department_id' => $employee->department_id,
                    'position_id' => $employee->position_id,
                    'company_alias_id' => $employee->company_alias_id,
                    'is_active' => $employee->is_active,
                ],
            ]);
        });

        return redirect()
            ->route('employees.index')
            ->with('alert', Flash::success('Funcionário atualizado', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        DB::transaction(function () use ($employee) {
            $snapshot = [
                'name' => $employee->name,
                'cpf' => $employee->cpf,
                'registration' => $employee->registration,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id,
                'company_alias_id' => $employee->company_alias_id,
                'is_active' => $employee->is_active,
            ];

            Audit::event('employees.deleted', $employee, $snapshot);

            $employee->delete();
        });

        return redirect()
            ->route('employees.index')
            ->with('alert', Flash::success('Funcionário removido', 'O funcionário foi removido com sucesso.'));
    }

    protected function formData(Request $request): array
    {
        $tenantId = $request->user()->tenant_id;

        $departments = Department::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
            ])
            ->values()
            ->toArray();

        $positions = Position::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Position $position) => [
                'id' => $position->id,
                'name' => $position->name,
            ])
            ->values()
            ->toArray();

        return [
            'departments' => $departments,
            'positions' => $positions,
        ];
    }

    // Sobrescrito no edit() para incluir companies — não exposto no create() intencionalmente
    protected function editFormData(Request $request): array
    {
        $tenantId = $request->user()->tenant_id;

        $companies = Company::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'alias'])
            ->map(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->alias ?? $company->name,
            ])
            ->values()
            ->toArray();

        return array_merge($this->formData($request), [
            'companies' => $companies,
        ]);
    }

    protected function formatCpf(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (strlen($digits) !== 11) {
            return $value;
        }

        return preg_replace(
            '/(\d{3})(\d{3})(\d{3})(\d{2})/',
            '$1.$2.$3-$4',
            $digits
        );
    }
}
