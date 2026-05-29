<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Support\Audit\Audit;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductCategory::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $productCategories = ProductCategory::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ProductCategory $productCategory) => [
                'id' => $productCategory->id,
                'name' => $productCategory->name,
                'description' => $productCategory->description,
                'is_active' => $productCategory->is_active,
                'created_at' => $productCategory->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('product-categories/Index', [
            'productCategories' => $productCategories,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', ProductCategory::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'product-categories-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $productCategories = ProductCategory::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($productCategories) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Descrição',
                'Status',
                'Criado em',
            ], ';');

            foreach ($productCategories as $productCategory) {
                fputcsv($handle, [
                    $productCategory->id,
                    $productCategory->name,
                    $productCategory->description,
                    $productCategory->is_active ? 'Ativa' : 'Inativa',
                    $productCategory->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', ProductCategory::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $productCategories = ProductCategory::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $productCategory) => [
                'id' => $productCategory->id,
                'name' => $productCategory->name,
                'description' => $productCategory->description,
                'status' => $productCategory->is_active ? 'Ativa' : 'Inativa',
                'created_at' => $productCategory->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.product-categories-report', [
                'productCategories' => $productCategories,
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
                'Content-Disposition' => 'attachment; filename="product-categories-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', ProductCategory::class);

        return Inertia::render('product-categories/Create');
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $productCategory = ProductCategory::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            Audit::event('product-categories.created', $productCategory, [
                'name' => $productCategory->name,
                'description' => $productCategory->description,
                'is_active' => $productCategory->is_active,
            ]);
        });

        return redirect()
            ->route('product-categories.index')
            ->with('alert', Flash::success('Categoria criada', 'A categoria foi criada com sucesso.'));
    }

    public function edit(ProductCategory $productCategory): Response
    {
        $this->authorize('update', $productCategory);

        return Inertia::render('product-categories/Edit', [
            'productCategory' => [
                'id' => $productCategory->id,
                'name' => $productCategory->name,
                'description' => $productCategory->description,
                'is_active' => $productCategory->is_active,
            ],
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        DB::transaction(function () use ($request, $productCategory) {
            $data = $request->validated();

            $before = [
                'name' => $productCategory->name,
                'description' => $productCategory->description,
                'is_active' => $productCategory->is_active,
            ];

            $productCategory->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            $after = [
                'name' => $productCategory->name,
                'description' => $productCategory->description,
                'is_active' => $productCategory->is_active,
            ];

            Audit::event('product-categories.updated', $productCategory, [
                'before' => $before,
                'after' => $after,
            ]);
        });

        return redirect()
            ->route('product-categories.index')
            ->with('alert', Flash::success('Categoria atualizada', 'A categoria foi atualizada com sucesso.'));
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $this->authorize('delete', $productCategory);

        DB::transaction(function () use ($productCategory) {
            Audit::event('product-categories.deleted', $productCategory, [
                'name' => $productCategory->name,
                'description' => $productCategory->description,
                'is_active' => $productCategory->is_active,
            ]);

            $productCategory->delete();
        });

        return redirect()
            ->route('product-categories.index')
            ->with('alert', Flash::success('Categoria excluída', 'A categoria foi excluída com sucesso.'));
    }
}
