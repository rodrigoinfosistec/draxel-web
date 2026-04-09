<?php

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Production\Http\Requests\StoreProductionEntryRequest;
use App\Modules\Production\Http\Requests\UpdateProductionEntryRequest;
use App\Modules\Production\Models\ProductionEntry;
use App\Modules\Production\Services\ProductionEntryService;
use App\Support\CompanyContext;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductionEntryController extends Controller
{
    public function __construct(
        protected ProductionEntryService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductionEntry::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $startDate = trim((string) $request->string('start_date')->value());
        $endDate = trim((string) $request->string('end_date')->value());
        $status = trim((string) $request->string('status')->value());
        $search = trim((string) $request->string('search')->value());

        $entries = ProductionEntry::query()
            ->with([
                'warehouse:id,name',
                'creator:id,name',
                'items:id,production_entry_id,quantity',
            ])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($warehouseId !== '', fn ($query) => $query->where('warehouse_id', (int) $warehouseId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($startDate !== '', fn ($query) => $query->whereDate('entry_date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('entry_date', '<=', $endDate))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('number', 'ilike', "%{$search}%")
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ProductionEntry $entry) => [
                'id' => $entry->id,
                'number' => $entry->number,
                'entry_date' => $entry->entry_date?->format('Y-m-d'),
                'warehouse_name' => $entry->warehouse?->name,
                'status' => $entry->status->value,
                'status_label' => $entry->status->label(),
                'items_count' => $entry->items->count(),
                'total_quantity' => round((float) $entry->items->sum('quantity'), 3),
                'created_by' => $entry->creator?->name,
            ]);

        return Inertia::render('production/entries/Index', [
            'entries' => $entries,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'search' => $search,
            ],
            'warehouses' => $this->warehouseOptions($tenantId, $companyId),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('export', ProductionEntry::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $startDate = trim((string) $request->string('start_date')->value());
        $endDate = trim((string) $request->string('end_date')->value());
        $status = trim((string) $request->string('status')->value());
        $search = trim((string) $request->string('search')->value());
        $filename = 'production-entries-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $entries = ProductionEntry::query()
            ->with([
                'warehouse:id,name',
                'creator:id,name',
                'items:id,production_entry_id,quantity',
            ])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($warehouseId !== '', fn ($query) => $query->where('warehouse_id', (int) $warehouseId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($startDate !== '', fn ($query) => $query->whereDate('entry_date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('entry_date', '<=', $endDate))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('number', 'ilike', "%{$search}%")
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        return response()->streamDownload(function () use ($entries) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Número',
                'Data',
                'Depósito',
                'Status',
                'Itens',
                'Quantidade Total',
                'Criado por',
                'Observação',
            ], ';');

            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry->id,
                    $entry->number,
                    $entry->entry_date?->format('d/m/Y'),
                    $entry->warehouse?->name,
                    $entry->status->label(),
                    $entry->items->count(),
                    number_format((float) $entry->items->sum('quantity'), 3, ',', '.'),
                    $entry->creator?->name,
                    $entry->notes,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('export', ProductionEntry::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $startDate = trim((string) $request->string('start_date')->value());
        $endDate = trim((string) $request->string('end_date')->value());
        $status = trim((string) $request->string('status')->value());
        $search = trim((string) $request->string('search')->value());

        $entries = ProductionEntry::query()
            ->with([
                'warehouse:id,name',
                'creator:id,name',
                'items:id,production_entry_id,quantity',
            ])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($warehouseId !== '', fn ($query) => $query->where('warehouse_id', (int) $warehouseId))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($startDate !== '', fn ($query) => $query->whereDate('entry_date', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('entry_date', '<=', $endDate))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('number', 'ilike', "%{$search}%")
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ProductionEntry $entry) => [
                'id' => $entry->id,
                'number' => $entry->number,
                'entry_date' => $entry->entry_date?->format('d/m/Y'),
                'warehouse_name' => $entry->warehouse?->name,
                'status_label' => $entry->status->label(),
                'items_count' => $entry->items->count(),
                'total_quantity' => number_format((float) $entry->items->sum('quantity'), 3, ',', '.'),
                'created_by' => $entry->creator?->name,
                'notes' => $entry->notes,
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.production-entries-report', [
                'entries' => $entries,
                'filters' => [
                    'warehouse_id' => $warehouseId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status,
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
                'Content-Disposition' => 'attachment; filename="production-entries-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ProductionEntry::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        return Inertia::render('production/entries/Create', [
            'warehouses' => $this->warehouseOptions($tenantId, $companyId),
            'products' => $this->productOptions($tenantId),
        ]);
    }

    public function store(StoreProductionEntryRequest $request): RedirectResponse
    {
        $entry = $this->service->create(
            data: $request->validated(),
            user: $request->user(),
        );

        return redirect()
            ->route('production.entries.show', $entry)
            ->with('alert', Flash::success('Entrada de produção cadastrada', 'A entrada de produção foi cadastrada com sucesso.'));
    }

    public function show(ProductionEntry $entry): Response
    {
        $this->authorize('view', $entry);

        $entry->load([
            'warehouse:id,name',
            'creator:id,name',
            'poster:id,name',
            'canceller:id,name',
            'items.product:id,name',
        ]);

        return Inertia::render('production/entries/Show', [
            'entry' => [
                'id' => $entry->id,
                'number' => $entry->number,
                'entry_date' => $entry->entry_date?->format('Y-m-d'),
                'warehouse_id' => $entry->warehouse_id,
                'warehouse_name' => $entry->warehouse?->name,
                'status' => $entry->status->value,
                'status_label' => $entry->status->label(),
                'notes' => $entry->notes,
                'created_by' => $entry->creator?->name,
                'posted_by' => $entry->poster?->name,
                'posted_at' => $entry->posted_at?->format('d/m/Y H:i'),
                'cancelled_by' => $entry->canceller?->name,
                'cancelled_at' => $entry->cancelled_at?->format('d/m/Y H:i'),
                'cancel_reason' => $entry->cancel_reason,
                'items' => $entry->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => $item->unit_cost !== null ? (float) $item->unit_cost : null,
                    'total_cost' => $item->total_cost !== null ? (float) $item->total_cost : null,
                    'notes' => $item->notes,
                ])->values()->all(),
            ],
        ]);
    }

    public function showPdf(Request $request, ProductionEntry $entry)
    {
        $this->authorize('view', $entry);

        $entry->load([
            'warehouse:id,name',
            'creator:id,name',
            'poster:id,name',
            'canceller:id,name',
            'items.product:id,name',
        ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.production-entry-show-report', [
                'entry' => [
                    'id' => $entry->id,
                    'number' => $entry->number,
                    'entry_date' => $entry->entry_date?->format('d/m/Y'),
                    'warehouse_name' => $entry->warehouse?->name,
                    'status_label' => $entry->status->label(),
                    'notes' => $entry->notes,
                    'created_by' => $entry->creator?->name,
                    'posted_by' => $entry->poster?->name,
                    'posted_at' => $entry->posted_at?->format('d/m/Y H:i'),
                    'cancelled_by' => $entry->canceller?->name,
                    'cancelled_at' => $entry->cancelled_at?->format('d/m/Y H:i'),
                    'cancel_reason' => $entry->cancel_reason,
                    'items' => $entry->items->map(fn ($item) => [
                        'product_name' => $item->product?->name,
                        'quantity' => number_format((float) $item->quantity, 3, ',', '.'),
                        'unit_cost' => $item->unit_cost !== null ? number_format((float) $item->unit_cost, 2, ',', '.') : '',
                        'total_cost' => $item->total_cost !== null ? number_format((float) $item->total_cost, 2, ',', '.') : '',
                        'notes' => $item->notes,
                    ])->values()->all(),
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
                'Content-Disposition' => 'attachment; filename="production-entry-' . $entry->number . '.pdf"',
            ]
        );
    }

    public function edit(Request $request, ProductionEntry $entry): Response
    {
        $this->authorize('update', $entry);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        $entry->load('items.product:id,name');

        return Inertia::render('production/entries/Edit', [
            'entry' => [
                'id' => $entry->id,
                'number' => $entry->number,
                'entry_date' => $entry->entry_date?->format('Y-m-d'),
                'warehouse_id' => $entry->warehouse_id,
                'notes' => $entry->notes,
                'status' => $entry->status->value,
                'items' => $entry->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => $item->unit_cost !== null ? (float) $item->unit_cost : null,
                    'notes' => $item->notes,
                ])->values()->all(),
            ],
            'warehouses' => $this->warehouseOptions($tenantId, $companyId),
            'products' => $this->productOptions($tenantId),
        ]);
    }

    public function update(UpdateProductionEntryRequest $request, ProductionEntry $entry): RedirectResponse
    {
        $this->service->update(
            entry: $entry,
            data: $request->validated(),
        );

        return redirect()
            ->route('production.entries.show', $entry)
            ->with('alert', Flash::success('Entrada de produção atualizada', 'A entrada de produção foi atualizada com sucesso.'));
    }

    public function post(Request $request, ProductionEntry $entry): RedirectResponse
    {
        $this->authorize('post', $entry);

        $this->service->post($entry, $request->user());

        return redirect()
            ->route('production.entries.show', $entry)
            ->with('alert', Flash::success('Entrada de produção lançada', 'A entrada de produção foi lançada com sucesso.'));
    }

    public function cancel(Request $request, ProductionEntry $entry): RedirectResponse
    {
        $this->authorize('cancel', $entry);

        $validated = $request->validate([
            'cancel_reason' => ['nullable', 'string'],
        ], [], [
            'cancel_reason' => 'motivo do cancelamento',
        ]);

        $this->service->cancel(
            entry: $entry,
            user: $request->user(),
            cancelReason: $validated['cancel_reason'] ?? null,
        );

        return redirect()
            ->route('production.entries.show', $entry)
            ->with('alert', Flash::success('Entrada de produção cancelada', 'A entrada de produção foi cancelada com sucesso.'));
    }

    public function destroy(ProductionEntry $entry): RedirectResponse
    {
        $this->authorize('delete', $entry);

        $this->service->delete($entry);

        return redirect()
            ->route('production.entries.index')
            ->with('alert', Flash::success('Entrada de produção excluída', 'A entrada de produção foi excluída com sucesso.'));
    }

    protected function warehouseOptions(int $tenantId, int $companyId)
    {
        return Warehouse::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ])
            ->values();
    }

    protected function productOptions(int $tenantId)
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
            ])
            ->values();
    }

    protected function statusOptions(): array
    {
        return [
            [
                'value' => 'draft',
                'label' => 'Rascunho',
            ],
            [
                'value' => 'posted',
                'label' => 'Lançado',
            ],
            [
                'value' => 'cancelled',
                'label' => 'Cancelado',
            ],
        ];
    }
}
