<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\Inventory\Enums\StockMovementSourceType;
use App\Modules\Inventory\Enums\StockMovementType;
use App\Modules\Inventory\Http\Requests\StoreStockMovementRequest;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\StockMovementService;
use App\Support\CompanyContext;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockMovementController extends Controller
{
    public function __construct(
        protected StockMovementService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StockMovement::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $productId = trim((string) $request->string('product_id')->value());
        $type = trim((string) $request->string('type')->value());
        $startDate = trim((string) $request->string('start_date')->value());
        $endDate = trim((string) $request->string('end_date')->value());
        $search = trim((string) $request->string('search')->value());

        $movements = StockMovement::query()
            ->with(['warehouse:id,name', 'product:id,name', 'user:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($warehouseId !== '', fn ($query) => $query->where('warehouse_id', (int) $warehouseId))
            ->when($productId !== '', fn ($query) => $query->where('product_id', (int) $productId))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($startDate !== '', fn ($query) => $query->whereDate('moved_at', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('moved_at', '<=', $endDate))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('reference', 'ilike', "%{$search}%")
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->latest('moved_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'warehouse_name' => $movement->warehouse?->name,
                'product_name' => $movement->product?->name,
                'type' => $movement->type?->value,
                'type_label' => $movement->type?->label(),
                'source_type' => $movement->source_type?->value,
                'source_label' => $movement->source_type?->label(),
                'quantity' => $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'reference' => $movement->reference,
                'notes' => $movement->notes,
                'moved_at' => $movement->moved_at?->format('d/m/Y H:i'),
                'user_name' => $movement->user?->name,
            ]);

        return Inertia::render('inventory/movements/Index', [
            'movements' => $movements,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
            ],
            'warehouses' => $this->warehouseOptions($tenantId, $companyId),
            'products' => $this->productOptions($tenantId),
            'typeOptions' => StockMovementType::formOptions(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('export', StockMovement::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $productId = trim((string) $request->string('product_id')->value());
        $type = trim((string) $request->string('type')->value());
        $startDate = trim((string) $request->string('start_date')->value());
        $endDate = trim((string) $request->string('end_date')->value());
        $search = trim((string) $request->string('search')->value());
        $filename = 'inventory-movements-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $movements = StockMovement::query()
            ->with(['warehouse:id,name', 'product:id,name', 'user:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($warehouseId !== '', fn ($query) => $query->where('warehouse_id', (int) $warehouseId))
            ->when($productId !== '', fn ($query) => $query->where('product_id', (int) $productId))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($startDate !== '', fn ($query) => $query->whereDate('moved_at', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('moved_at', '<=', $endDate))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('reference', 'ilike', "%{$search}%")
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->latest('moved_at')
            ->get();

        return response()->streamDownload(function () use ($movements) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Depósito',
                'Produto',
                'Tipo',
                'Origem',
                'Quantidade',
                'Custo Unitário',
                'Referência',
                'Observação',
                'Movimentado em',
                'Usuário',
            ], ';');

            foreach ($movements as $movement) {
                fputcsv($handle, [
                    $movement->id,
                    $movement->warehouse?->name,
                    $movement->product?->name,
                    $movement->type?->label(),
                    $movement->source_type?->label(),
                    number_format((float) $movement->quantity, 3, ',', '.'),
                    $movement->unit_cost !== null ? number_format((float) $movement->unit_cost, 2, ',', '.') : '',
                    $movement->reference,
                    $movement->notes,
                    $movement->moved_at?->format('d/m/Y H:i:s'),
                    $movement->user?->name,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('export', StockMovement::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $productId = trim((string) $request->string('product_id')->value());
        $type = trim((string) $request->string('type')->value());
        $startDate = trim((string) $request->string('start_date')->value());
        $endDate = trim((string) $request->string('end_date')->value());
        $search = trim((string) $request->string('search')->value());

        $movements = StockMovement::query()
            ->with(['warehouse:id,name', 'product:id,name', 'user:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($warehouseId !== '', fn ($query) => $query->where('warehouse_id', (int) $warehouseId))
            ->when($productId !== '', fn ($query) => $query->where('product_id', (int) $productId))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($startDate !== '', fn ($query) => $query->whereDate('moved_at', '>=', $startDate))
            ->when($endDate !== '', fn ($query) => $query->whereDate('moved_at', '<=', $endDate))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('reference', 'ilike', "%{$search}%")
                        ->orWhere('notes', 'ilike', "%{$search}%")
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->latest('moved_at')
            ->get()
            ->map(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'warehouse_name' => $movement->warehouse?->name,
                'product_name' => $movement->product?->name,
                'type' => $movement->type?->label(),
                'source_type' => $movement->source_type?->label(),
                'quantity' => number_format((float) $movement->quantity, 3, ',', '.'),
                'unit_cost' => $movement->unit_cost !== null ? number_format((float) $movement->unit_cost, 2, ',', '.') : '',
                'reference' => $movement->reference,
                'notes' => $movement->notes,
                'moved_at' => $movement->moved_at?->format('d/m/Y H:i:s'),
                'user_name' => $movement->user?->name,
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.inventory-movements-report', [
                'movements' => $movements,
                'filters' => [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'type' => $type,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
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
                'Content-Disposition' => 'attachment; filename="inventory-movements-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', StockMovement::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        return Inertia::render('inventory/movements/Create', [
            'warehouses' => $this->warehouseOptions($tenantId, $companyId),
            'products' => $this->productOptions($tenantId),
            'typeOptions' => StockMovementType::formOptions(),
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        $this->service->register([
            'tenant_id' => $request->user()->tenant_id,
            'company_id' => session('current_company_id'),
            'warehouse_id' => (int) $request->integer('warehouse_id'),
            'product_id' => (int) $request->integer('product_id'),
            'type' => $request->string('type')->toString(),
            'source_type' => StockMovementSourceType::Manual,
            'quantity' => $request->input('quantity'),
            'unit_cost' => $request->input('unit_cost'),
            'reference' => $request->input('reference'),
            'notes' => $request->input('notes'),
            'moved_at' => $request->input('moved_at'),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('inventory.movements.index')
            ->with('alert', Flash::success('Movimentação criada', 'A movimentação foi registrada com sucesso.'));
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
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
            ])
            ->values();
    }
}
