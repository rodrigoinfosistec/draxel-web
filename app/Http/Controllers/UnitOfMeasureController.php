<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitOfMeasureRequest;
use App\Http\Requests\UpdateUnitOfMeasureRequest;
use App\Models\UnitOfMeasure;
use App\Support\Audit\Audit;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnitOfMeasureController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', UnitOfMeasure::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $unitOfMeasures = UnitOfMeasure::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('symbol', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (UnitOfMeasure $unitOfMeasure) => [
                'id' => $unitOfMeasure->id,
                'name' => $unitOfMeasure->name,
                'symbol' => $unitOfMeasure->symbol,
                'description' => $unitOfMeasure->description,
                'is_active' => $unitOfMeasure->is_active,
                'created_at' => $unitOfMeasure->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('unit-of-measures/Index', [
            'unitOfMeasures' => $unitOfMeasures,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', UnitOfMeasure::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'unit-of-measures-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $unitOfMeasures = UnitOfMeasure::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('symbol', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($unitOfMeasures) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Sigla',
                'Descrição',
                'Status',
                'Criado em',
            ], ';');

            foreach ($unitOfMeasures as $unitOfMeasure) {
                fputcsv($handle, [
                    $unitOfMeasure->id,
                    $unitOfMeasure->name,
                    $unitOfMeasure->symbol,
                    $unitOfMeasure->description,
                    $unitOfMeasure->is_active ? 'Ativa' : 'Inativa',
                    $unitOfMeasure->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', UnitOfMeasure::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $unitOfMeasures = UnitOfMeasure::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('symbol', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get()
            ->map(fn (UnitOfMeasure $unitOfMeasure) => [
                'id' => $unitOfMeasure->id,
                'name' => $unitOfMeasure->name,
                'symbol' => $unitOfMeasure->symbol,
                'description' => $unitOfMeasure->description,
                'status' => $unitOfMeasure->is_active ? 'Ativa' : 'Inativa',
                'created_at' => $unitOfMeasure->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.unit-of-measures-report', [
                'unitOfMeasures' => $unitOfMeasures,
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
                'Content-Disposition' => 'attachment; filename="unit-of-measures-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', UnitOfMeasure::class);

        return Inertia::render('unit-of-measures/Create');
    }

    public function store(StoreUnitOfMeasureRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $unitOfMeasure = UnitOfMeasure::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'symbol' => $data['symbol'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            Audit::event('unit-of-measures.created', $unitOfMeasure, [
                'name' => $unitOfMeasure->name,
                'symbol' => $unitOfMeasure->symbol,
                'description' => $unitOfMeasure->description,
                'is_active' => $unitOfMeasure->is_active,
            ]);
        });

        return redirect()
            ->route('unit-of-measures.index')
            ->with('alert', Flash::success('Unidade criada', 'A unidade de medida foi criada com sucesso.'));
    }

    public function edit(UnitOfMeasure $unitOfMeasure): Response
    {
        $this->authorize('update', $unitOfMeasure);

        return Inertia::render('unit-of-measures/Edit', [
            'unitOfMeasure' => [
                'id' => $unitOfMeasure->id,
                'name' => $unitOfMeasure->name,
                'symbol' => $unitOfMeasure->symbol,
                'description' => $unitOfMeasure->description,
                'is_active' => $unitOfMeasure->is_active,
            ],
        ]);
    }

    public function update(UpdateUnitOfMeasureRequest $request, UnitOfMeasure $unitOfMeasure): RedirectResponse
    {
        DB::transaction(function () use ($request, $unitOfMeasure) {
            $data = $request->validated();

            $before = [
                'name' => $unitOfMeasure->name,
                'symbol' => $unitOfMeasure->symbol,
                'description' => $unitOfMeasure->description,
                'is_active' => $unitOfMeasure->is_active,
            ];

            $unitOfMeasure->update([
                'name' => $data['name'],
                'symbol' => $data['symbol'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            $after = [
                'name' => $unitOfMeasure->name,
                'symbol' => $unitOfMeasure->symbol,
                'description' => $unitOfMeasure->description,
                'is_active' => $unitOfMeasure->is_active,
            ];

            Audit::event('unit-of-measures.updated', $unitOfMeasure, [
                'before' => $before,
                'after' => $after,
            ]);
        });

        return redirect()
            ->route('unit-of-measures.index')
            ->with('alert', Flash::success('Unidade atualizada', 'A unidade de medida foi atualizada com sucesso.'));
    }

    public function destroy(UnitOfMeasure $unitOfMeasure): RedirectResponse
    {
        $this->authorize('delete', $unitOfMeasure);

        DB::transaction(function () use ($unitOfMeasure) {
            Audit::event('unit-of-measures.deleted', $unitOfMeasure, [
                'name' => $unitOfMeasure->name,
                'symbol' => $unitOfMeasure->symbol,
                'description' => $unitOfMeasure->description,
                'is_active' => $unitOfMeasure->is_active,
            ]);

            $unitOfMeasure->delete();
        });

        return redirect()
            ->route('unit-of-measures.index')
            ->with('alert', Flash::success('Unidade excluída', 'A unidade de medida foi excluída com sucesso.'));
    }
}
