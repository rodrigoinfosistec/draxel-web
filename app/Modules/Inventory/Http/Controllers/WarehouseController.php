<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Http\Requests\StoreWarehouseRequest;
use App\Modules\Inventory\Http\Requests\UpdateWarehouseRequest;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\WarehouseService;
use App\Support\CompanyContext;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseController extends Controller
{
    public function __construct(
        protected WarehouseService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Warehouse::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = trim((string) $request->string('search')->value());

        $warehouses = Warehouse::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'description' => $warehouse->description,
                'is_active' => $warehouse->is_active,
            ]);

        return Inertia::render('inventory/warehouses/Index', [
            'warehouses' => $warehouses,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('export', Warehouse::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = trim((string) $request->string('search')->value());
        $filename = 'inventory-warehouses-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $warehouses = Warehouse::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($warehouses) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Código',
                'Descrição',
                'Ativo',
                'Criado em',
            ], ';');

            foreach ($warehouses as $warehouse) {
                fputcsv($handle, [
                    $warehouse->id,
                    $warehouse->name,
                    $warehouse->code,
                    $warehouse->description,
                    $warehouse->is_active ? 'Sim' : 'Não',
                    $warehouse->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('export', Warehouse::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = trim((string) $request->string('search')->value());

        $warehouses = Warehouse::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'description' => $warehouse->description,
                'is_active' => $warehouse->is_active ? 'Sim' : 'Não',
                'created_at' => $warehouse->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.inventory-warehouses-report', [
                'warehouses' => $warehouses,
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
                'Content-Disposition' => 'attachment; filename="inventory-warehouses-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Warehouse::class);

        return Inertia::render('inventory/warehouses/Create');
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $this->service->create(
            data: $request->validated(),
            user: $request->user(),
        );

        return redirect()
            ->route('inventory.warehouses.index')
            ->with('alert', Flash::success('Depósito criado', 'O depósito foi criado com sucesso.'));
    }

    public function edit(Warehouse $warehouse): Response
    {
        $this->authorize('update', $warehouse);

        return Inertia::render('inventory/warehouses/Edit', [
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'description' => $warehouse->description,
                'is_active' => $warehouse->is_active,
            ],
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->service->update(
            warehouse: $warehouse,
            data: $request->validated(),
        );

        return redirect()
            ->route('inventory.warehouses.index')
            ->with('alert', Flash::success('Depósito atualizado', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('delete', $warehouse);

        try {
            $this->service->delete($warehouse);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('inventory.warehouses.index')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('inventory.warehouses.index')
            ->with('alert', Flash::success('Depósito removido', 'O depósito foi removido com sucesso.'));
    }
}
