<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Models\Holiday;
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

class HolidayController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Holiday::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $holidays = Holiday::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('date')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Holiday $holiday) => [
                'id' => $holiday->id,
                'name' => $holiday->name,
                'date' => $holiday->date?->format('Y-m-d'),
                'date_label' => $holiday->date?->format('d/m/Y'),
                'description' => $holiday->description,
            ]);

        return Inertia::render('holidays/Index', [
            'holidays' => $holidays,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Holiday::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'holidays-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $holidays = Holiday::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('date')
            ->get();

        return response()->streamDownload(function () use ($holidays) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Data',
                'Descricao',
                'Criado em',
            ], ';');

            foreach ($holidays as $holiday) {
                fputcsv($handle, [
                    $holiday->id,
                    $holiday->name,
                    $holiday->date?->format('d/m/Y'),
                    $holiday->description,
                    $holiday->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Holiday::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $holidays = Holiday::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->orderBy('date')
            ->get()
            ->map(fn (Holiday $holiday) => [
                'id' => $holiday->id,
                'name' => $holiday->name,
                'date' => $holiday->date?->format('d/m/Y'),
                'description' => $holiday->description,
                'created_at' => $holiday->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.holidays-report', [
                'holidays' => $holidays,
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
                'Content-Disposition' => 'attachment; filename="holidays-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Holiday::class);

        return Inertia::render('holidays/Create');
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $holiday = Holiday::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
            ]);

            Audit::event('holidays.created', $holiday, [
                'name' => $holiday->name,
                'date' => $holiday->date?->format('Y-m-d'),
                'description' => $holiday->description,
            ]);
        });

        return redirect()
            ->route('holidays.index')
            ->with('alert', Flash::success('Feriado criado', 'O feriado foi criado com sucesso.'));
    }

    public function edit(Holiday $holiday): Response
    {
        $this->authorize('update', $holiday);

        return Inertia::render('holidays/Edit', [
            'holiday' => [
                'id' => $holiday->id,
                'name' => $holiday->name,
                'date' => $holiday->date?->format('Y-m-d'),
                'description' => $holiday->description,
            ],
        ]);
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        DB::transaction(function () use ($request, $holiday) {
            $data = $request->validated();

            $before = [
                'name' => $holiday->name,
                'date' => $holiday->date?->format('Y-m-d'),
                'description' => $holiday->description,
            ];

            $holiday->update([
                'name' => $data['name'],
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
            ]);

            Audit::event('holidays.updated', $holiday, [
                'before' => $before,
                'after' => [
                    'name' => $holiday->name,
                    'date' => $holiday->date?->format('Y-m-d'),
                    'description' => $holiday->description,
                ],
            ]);
        });

        return redirect()
            ->route('holidays.index')
            ->with('alert', Flash::success('Feriado atualizado', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorize('delete', $holiday);

        DB::transaction(function () use ($holiday) {
            $snapshot = [
                'name' => $holiday->name,
                'date' => $holiday->date?->format('Y-m-d'),
                'description' => $holiday->description,
            ];

            Audit::event('holidays.deleted', $holiday, $snapshot);

            $holiday->delete();
        });

        return redirect()
            ->route('holidays.index')
            ->with('alert', Flash::success('Feriado removido', 'O feriado foi removido com sucesso.'));
    }
}
