<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\ProductStock;
use App\Modules\Inventory\Models\Warehouse;
use App\Support\CompanyContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryPositionController extends Controller
{
    public function byWarehouse(Request $request): Response
    {
        $this->authorize('viewAny', ProductStock::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $search = trim((string) $request->string('search')->value());

        $positions = ProductStock::query()
            ->with(['warehouse:id,name', 'product:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($warehouseId !== '', fn ($query) => $query->where('warehouse_id', (int) $warehouseId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->whereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('quantity')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ProductStock $position) => [
                'id' => $position->id,
                'warehouse_name' => $position->warehouse?->name,
                'product_name' => $position->product?->name,
                'quantity' => $position->quantity,
            ]);

        $warehouses = Warehouse::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Warehouse $warehouse) => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
            ])
            ->values();

        return Inertia::render('inventory/positions/Index', [
            'positions' => $positions,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'search' => $search,
            ],
            'warehouses' => $warehouses,
        ]);
    }

    public function exportByWarehouseCsv(Request $request): StreamedResponse
    {
        $this->authorize('export', ProductStock::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $search = trim((string) $request->string('search')->value());
        $filename = 'inventory-positions-by-warehouse-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $positions = ProductStock::query()
            ->with(['warehouse:id,name', 'product:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($warehouseId !== '', fn ($query) => $query->where('warehouse_id', (int) $warehouseId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->whereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('quantity')
            ->get();

        return response()->streamDownload(function () use ($positions) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Depósito',
                'Produto',
                'Saldo',
            ], ';');

            foreach ($positions as $position) {
                fputcsv($handle, [
                    $position->id,
                    $position->warehouse?->name,
                    $position->product?->name,
                    number_format((float) $position->quantity, 3, ',', '.'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportByWarehousePdf(Request $request)
    {
        $this->authorize('export', ProductStock::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $search = trim((string) $request->string('search')->value());

        $positions = ProductStock::query()
            ->with(['warehouse:id,name', 'product:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($warehouseId !== '', fn ($query) => $query->where('warehouse_id', (int) $warehouseId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->whereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('quantity')
            ->get()
            ->map(fn (ProductStock $position) => [
                'id' => $position->id,
                'warehouse_name' => $position->warehouse?->name,
                'product_name' => $position->product?->name,
                'quantity' => number_format((float) $position->quantity, 3, ',', '.'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.inventory-positions-report', [
                'positions' => $positions,
                'filters' => [
                    'warehouse_id' => $warehouseId,
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
                'Content-Disposition' => 'attachment; filename="inventory-positions-by-warehouse-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function consolidated(Request $request): Response
    {
        $this->authorize('viewConsolidated', ProductStock::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = trim((string) $request->string('search')->value());

        $positions = ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('product_stocks.tenant_id', $tenantId)
            ->where('product_stocks.company_id', $companyId)
            ->when($search !== '', fn ($query) => $query->where('products.name', 'ilike', "%{$search}%"))
            ->groupBy('product_stocks.product_id', 'products.name')
            ->selectRaw('product_stocks.product_id as product_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('SUM(product_stocks.quantity) as total_quantity')
            ->orderBy('products.name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($position) => [
                'product_id' => $position->product_id,
                'product_name' => $position->product_name,
                'total_quantity' => (string) $position->total_quantity,
            ]);

        return Inertia::render('inventory/positions/Consolidated', [
            'positions' => $positions,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportConsolidatedCsv(Request $request): StreamedResponse
    {
        $this->authorize('exportConsolidated', ProductStock::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = trim((string) $request->string('search')->value());
        $filename = 'inventory-positions-consolidated-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $positions = ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('product_stocks.tenant_id', $tenantId)
            ->where('product_stocks.company_id', $companyId)
            ->when($search !== '', fn ($query) => $query->where('products.name', 'ilike', "%{$search}%"))
            ->groupBy('product_stocks.product_id', 'products.name')
            ->selectRaw('product_stocks.product_id as product_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('SUM(product_stocks.quantity) as total_quantity')
            ->orderBy('products.name')
            ->get();

        return response()->streamDownload(function () use ($positions) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Produto',
                'Saldo Total',
            ], ';');

            foreach ($positions as $position) {
                fputcsv($handle, [
                    $position->product_name,
                    number_format((float) $position->total_quantity, 3, ',', '.'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportConsolidatedPdf(Request $request)
    {
        $this->authorize('exportConsolidated', ProductStock::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');
        $search = trim((string) $request->string('search')->value());

        $positions = ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('product_stocks.tenant_id', $tenantId)
            ->where('product_stocks.company_id', $companyId)
            ->when($search !== '', fn ($query) => $query->where('products.name', 'ilike', "%{$search}%"))
            ->groupBy('product_stocks.product_id', 'products.name')
            ->selectRaw('product_stocks.product_id as product_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('SUM(product_stocks.quantity) as total_quantity')
            ->orderBy('products.name')
            ->get()
            ->map(fn ($position) => [
                'product_name' => $position->product_name,
                'total_quantity' => number_format((float) $position->total_quantity, 3, ',', '.'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.inventory-consolidated-positions-report', [
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
                'Content-Disposition' => 'attachment; filename="inventory-positions-consolidated-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }
}
