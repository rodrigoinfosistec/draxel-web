<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Support\Audit\Audit;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $departments = Department::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
                'created_at' => $department->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('departments/Index', [
            'departments' => $departments,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Department::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'departments-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $departments = Department::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($departments) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Slug',
                'Descrição',
                'Criado em',
            ], ';');

            foreach ($departments as $department) {
                fputcsv($handle, [
                    $department->id,
                    $department->name,
                    $department->slug,
                    $department->description,
                    $department->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Department::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $departments = Department::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
                'created_at' => $department->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.departments-report', [
                'departments' => $departments,
                'filters' => [
                    'search' => $search,
                ],
                'generatedAt' => now()->format('d/m/Y H:i:s'),
                'tenantName' => $request->user()->tenant?->name ?? 'Tenant',
                'companyName' => null,
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
                'Content-Disposition' => 'attachment; filename="departments-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Department::class);

        return Inertia::render('departments/Create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $department = Department::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
            ]);

            Audit::event('departments.created', $department, [
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
            ]);
        });

        return redirect()
            ->route('departments.index')
            ->with('alert', Flash::success('Departamento criado', 'O departamento foi criado com sucesso.'));
    }

    public function edit(Department $department): Response
    {
        $this->authorize('update', $department);

        return Inertia::render('departments/Edit', [
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
            ],
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        DB::transaction(function () use ($request, $department) {
            $data = $request->validated();

            $before = [
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
            ];

            $department->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
            ]);

            Audit::event('departments.updated', $department, [
                'before' => $before,
                'after' => [
                    'name' => $department->name,
                    'slug' => $department->slug,
                    'description' => $department->description,
                ],
            ]);
        });

        return redirect()
            ->route('departments.index')
            ->with('alert', Flash::success('Departamento atualizado', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        DB::transaction(function () use ($department) {
            $snapshot = [
                'name' => $department->name,
                'slug' => $department->slug,
                'description' => $department->description,
            ];

            Audit::event('departments.deleted', $department, $snapshot);

            $department->delete();
        });

        return redirect()
            ->route('departments.index')
            ->with('alert', Flash::success('Departamento removido', 'O departamento foi removido com sucesso.'));
    }
}
