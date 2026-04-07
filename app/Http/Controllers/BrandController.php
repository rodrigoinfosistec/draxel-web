<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use App\Support\Audit\Audit;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Brand::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $brands = Brand::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'description' => $brand->description,
                'is_active' => $brand->is_active,
                'created_at' => $brand->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('brands/Index', [
            'brands' => $brands,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Brand::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'brands-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $brands = Brand::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($brands) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Descrição',
                'Status',
                'Criado em',
            ], ';');

            foreach ($brands as $brand) {
                fputcsv($handle, [
                    $brand->id,
                    $brand->name,
                    $brand->description,
                    $brand->is_active ? 'Ativa' : 'Inativa',
                    $brand->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Brand::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $brands = Brand::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'description' => $brand->description,
                'status' => $brand->is_active ? 'Ativa' : 'Inativa',
                'created_at' => $brand->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.brands-report', [
                'brands' => $brands,
                'filters' => [
                    'search' => $search,
                ],
                'generatedAt' => now()->format('d/m/Y H:i:s'),
                'tenantName' => $request->user()->tenant?->name ?? 'Tenant',
                'companyName' => 'Todas as empresas',
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
                'Content-Disposition' => 'attachment; filename="brands-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Brand::class);

        return Inertia::render('brands/Create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $brand = Brand::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            Audit::event('brands.created', $brand, [
                'name' => $brand->name,
                'description' => $brand->description,
                'is_active' => $brand->is_active,
            ]);
        });

        return redirect()
            ->route('brands.index')
            ->with('alert', Flash::success('Marca criada', 'A marca foi criada com sucesso.'));
    }

    public function edit(Brand $brand): Response
    {
        $this->authorize('update', $brand);

        return Inertia::render('brands/Edit', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'description' => $brand->description,
                'is_active' => $brand->is_active,
            ],
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        DB::transaction(function () use ($request, $brand) {
            $data = $request->validated();

            $before = [
                'name' => $brand->name,
                'description' => $brand->description,
                'is_active' => $brand->is_active,
            ];

            $brand->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            $after = [
                'name' => $brand->name,
                'description' => $brand->description,
                'is_active' => $brand->is_active,
            ];

            Audit::event('brands.updated', $brand, [
                'before' => $before,
                'after' => $after,
            ]);
        });

        return redirect()
            ->route('brands.index')
            ->with('alert', Flash::success('Marca atualizada', 'A marca foi atualizada com sucesso.'));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        DB::transaction(function () use ($brand) {
            Audit::event('brands.deleted', $brand, [
                'name' => $brand->name,
                'description' => $brand->description,
                'is_active' => $brand->is_active,
            ]);

            $brand->delete();
        });

        return redirect()
            ->route('brands.index')
            ->with('alert', Flash::success('Marca excluída', 'A marca foi excluída com sucesso.'));
    }
}
