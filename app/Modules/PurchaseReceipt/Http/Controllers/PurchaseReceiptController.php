<?php

namespace App\Modules\PurchaseReceipt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\PurchaseReceipt\Enums\PurchaseReceiptStatus;
use App\Modules\PurchaseReceipt\Http\Requests\StorePurchaseReceiptRequest;
use App\Modules\PurchaseReceipt\Http\Requests\UpdatePurchaseReceiptRequest;
use App\Modules\PurchaseReceipt\Models\PurchaseReceipt;
use App\Modules\PurchaseReceipt\Services\PurchaseReceiptService;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseReceiptController extends Controller
{
    public function __construct(
        protected PurchaseReceiptService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseReceipt::class);

        $filters = $this->filters($request);

        $purchaseReceipts = $this->queryWithFilters($filters)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('purchase-receipt/receipts/Index', [
            'purchaseReceipts' => $purchaseReceipts,
            'filters' => $filters,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'statuses' => collect(PurchaseReceiptStatus::cases())->map(fn (PurchaseReceiptStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PurchaseReceipt::class);

        return Inertia::render('purchase-receipt/receipts/Create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StorePurchaseReceiptRequest $request): RedirectResponse
    {
        $receipt = $this->service->store($request->validated());

        return redirect()
            ->route('purchase-receipts.receipts.show', $receipt)
            ->with('alert', Flash::success('Recebimento cadastrado com sucesso.'));
    }

    public function show(PurchaseReceipt $purchaseReceipt): Response
    {
        $this->authorize('view', $purchaseReceipt);

        $purchaseReceipt->load([
            'supplier:id,name',
            'warehouse:id,name',
            'receivedBy:id,name',
            'items.product:id,name',
        ]);

        return Inertia::render('purchase-receipt/receipts/Show', [
            'purchaseReceipt' => $purchaseReceipt,
        ]);
    }

    public function edit(PurchaseReceipt $purchaseReceipt): Response
    {
        $this->authorize('update', $purchaseReceipt);

        $purchaseReceipt->load([
            'items.product:id,name',
        ]);

        return Inertia::render('purchase-receipt/receipts/Edit', [
            'purchaseReceipt' => $purchaseReceipt,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePurchaseReceiptRequest $request, PurchaseReceipt $purchaseReceipt): RedirectResponse
    {
        $this->authorize('update', $purchaseReceipt);

        $this->service->update($purchaseReceipt, $request->validated());

        return redirect()
            ->route('purchase-receipts.receipts.show', $purchaseReceipt)
            ->with('alert', Flash::success('Recebimento atualizado com sucesso.'));
    }

    public function destroy(PurchaseReceipt $purchaseReceipt): RedirectResponse
    {
        $this->authorize('delete', $purchaseReceipt);

        $this->service->delete($purchaseReceipt);

        return redirect()
            ->route('purchase-receipts.receipts.index')
            ->with('alert', Flash::success('Recebimento excluído com sucesso.'));
    }

    public function post(PurchaseReceipt $purchaseReceipt): RedirectResponse
    {
        $this->authorize('post', $purchaseReceipt);

        $this->service->post($purchaseReceipt);

        return redirect()
            ->route('purchase-receipts.receipts.show', $purchaseReceipt)
            ->with('alert', Flash::success('Recebimento lançado com sucesso.'));
    }

    public function cancel(PurchaseReceipt $purchaseReceipt): RedirectResponse
    {
        $this->authorize('cancel', $purchaseReceipt);

        $this->service->cancel($purchaseReceipt);

        return redirect()
            ->route('purchase-receipts.receipts.show', $purchaseReceipt)
            ->with('alert', Flash::success('Recebimento cancelado com sucesso.'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('export', PurchaseReceipt::class);

        $filters = $this->filters($request);

        $rows = $this->queryWithFilters($filters)
            ->latest('id')
            ->get();

        $filename = 'recebimentos-compra-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Número',
                'Nota fiscal',
                'Fornecedor',
                'Depósito',
                'Data de recebimento',
                'Status',
                'Valor total',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->number,
                    $row->invoice_number,
                    $row->supplier?->name,
                    $row->warehouse?->name,
                    optional($row->receipt_date)->format('d/m/Y'),
                    $row->status_label,
                    number_format((float) $row->total_amount, 2, ',', '.'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('export', PurchaseReceipt::class);

        $filters = $this->filters($request);

        $rows = $this->queryWithFilters($filters)
            ->latest('id')
            ->get()
            ->map(function (PurchaseReceipt $receipt) {
                return [
                    'id' => $receipt->id,
                    'number' => $receipt->number ?: '—',
                    'invoice_number' => $receipt->invoice_number ?: '—',
                    'supplier_name' => $receipt->supplier?->name ?: '—',
                    'warehouse_name' => $receipt->warehouse?->name ?: '—',
                    'receipt_date' => optional($receipt->receipt_date)->format('d/m/Y') ?: '—',
                    'status_label' => $receipt->status_label,
                    'total_amount' => 'R$ ' . number_format((float) $receipt->total_amount, 2, ',', '.'),
                ];
            })
            ->values();

        $filterSummary = [
            'Busca' => $filters['search'] ?: 'Todos',
            'Status' => $this->statusLabel($filters['status']),
            'Fornecedor' => $filters['supplier_name'] ?: 'Todos',
            'Depósito' => $filters['warehouse_name'] ?: 'Todos',
            'Período' => $this->periodLabel($filters['start_date'], $filters['end_date']),
        ];

        $pdf = Pdf::loadView('pdf.purchase-receipts', [
            'receipts' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'filterSummary' => $filterSummary,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('recebimentos-compra-' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    protected function filters(Request $request): array
    {
        $supplierId = $request->integer('supplier_id') ?: null;
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $supplierName = null;
        $warehouseName = null;

        if ($supplierId) {
            $supplierName = Supplier::query()->whereKey($supplierId)->value('name');
        }

        if ($warehouseId) {
            $warehouseName = Warehouse::query()->whereKey($warehouseId)->value('name');
        }

        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'supplier_id' => $supplierId,
            'warehouse_id' => $warehouseId,
            'start_date' => $request->string('start_date')->toString(),
            'end_date' => $request->string('end_date')->toString(),
            'supplier_name' => $supplierName,
            'warehouse_name' => $warehouseName,
        ];
    }

    protected function queryWithFilters(array $filters): Builder
    {
        return PurchaseReceipt::query()
            ->forCurrentContext()
            ->with(['supplier:id,name', 'warehouse:id,name'])
            ->when($filters['search'], function (Builder $query, string $search) {
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->where('number', 'like', '%' . Str::of($search)->trim() . '%')
                        ->orWhere('invoice_number', 'like', '%' . Str::of($search)->trim() . '%');
                });
            })
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['supplier_id'], fn (Builder $query, int $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($filters['warehouse_id'], fn (Builder $query, int $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($filters['start_date'], fn (Builder $query, string $startDate) => $query->whereDate('receipt_date', '>=', $startDate))
            ->when($filters['end_date'], fn (Builder $query, string $endDate) => $query->whereDate('receipt_date', '<=', $endDate));
    }

    protected function statusLabel(string $status): string
    {
        if ($status === '') {
            return 'Todos';
        }

        return collect(PurchaseReceiptStatus::cases())
            ->firstWhere('value', $status)?->label() ?? $status;
    }

    protected function periodLabel(string $startDate, string $endDate): string
    {
        if ($startDate !== '' && $endDate !== '') {
            return now()->parse($startDate)->format('d/m/Y') . ' até ' . now()->parse($endDate)->format('d/m/Y');
        }

        if ($startDate !== '') {
            return 'A partir de ' . now()->parse($startDate)->format('d/m/Y');
        }

        if ($endDate !== '') {
            return 'Até ' . now()->parse($endDate)->format('d/m/Y');
        }

        return 'Todos';
    }
}
