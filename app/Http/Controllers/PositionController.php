<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
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

class PositionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Position::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $positions = Position::query()
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
            ->through(fn (Position $position) => [
                'id' => $position->id,
                'slug' => $position->slug,
                'name' => $position->name,
                'description' => $position->description,
            ]);

        return Inertia::render('positions/Index', [
            'positions' => $positions,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Position::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'positions-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $positions = Position::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($positions) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Slug',
                'Nome',
                'Descricao',
                'Criado em',
            ], ';');

            foreach ($positions as $position) {
                fputcsv($handle, [
                    $position->id,
                    $position->slug,
                    $position->name,
                    $position->description,
                    $position->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Position::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $positions = Position::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('name')
            ->get()
            ->map(fn (Position $position) => [
                'id' => $position->id,
                'slug' => $position->slug,
                'name' => $position->name,
                'description' => $position->description,
                'created_at' => $position->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.positions-report', [
                'positions' => $positions,
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
                'Content-Disposition' => 'attachment; filename="positions-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Position::class);

        return Inertia::render('positions/Create');
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $position = Position::create([
                'tenant_id' => $tenantId,
                'slug' => $data['slug'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            Audit::event('positions.created', $position, [
                'slug' => $position->slug,
                'name' => $position->name,
                'description' => $position->description,
            ]);
        });

        return redirect()
            ->route('positions.index')
            ->with('alert', Flash::success('Cargo criado', 'O cargo foi criado com sucesso.'));
    }

    public function edit(Position $position): Response
    {
        $this->authorize('update', $position);

        return Inertia::render('positions/Edit', [
            'position' => [
                'id' => $position->id,
                'slug' => $position->slug,
                'name' => $position->name,
                'description' => $position->description,
            ],
        ]);
    }

    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        DB::transaction(function () use ($request, $position) {
            $data = $request->validated();

            $before = [
                'slug' => $position->slug,
                'name' => $position->name,
                'description' => $position->description,
            ];

            $position->update([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            Audit::event('positions.updated', $position, [
                'before' => $before,
                'after' => [
                    'slug' => $position->slug,
                    'name' => $position->name,
                    'description' => $position->description,
                ],
            ]);
        });

        return redirect()
            ->route('positions.index')
            ->with('alert', Flash::success('Cargo atualizado', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(Position $position): RedirectResponse
    {
        $this->authorize('delete', $position);

        DB::transaction(function () use ($position) {
            $snapshot = [
                'slug' => $position->slug,
                'name' => $position->name,
                'description' => $position->description,
            ];

            Audit::event('positions.deleted', $position, $snapshot);

            $position->delete();
        });

        return redirect()
            ->route('positions.index')
            ->with('alert', Flash::success('Cargo removido', 'O cargo foi removido com sucesso.'));
    }
}
