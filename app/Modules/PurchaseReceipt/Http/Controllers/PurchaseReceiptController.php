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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReceiptController extends Controller
{
    public function __construct(
        protected PurchaseReceiptService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseReceipt::class);

        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'supplier_id' => $request->integer('supplier_id') ?: null,
            'warehouse_id' => $request->integer('warehouse_id') ?: null,
            'start_date' => $request->string('start_date')->toString(),
            'end_date' => $request->string('end_date')->toString(),
        ];

        $purchaseReceipts = PurchaseReceipt::query()
            ->forCurrentContext()
            ->with(['supplier:id,name', 'warehouse:id,name'])
            ->when($filters['search'], function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('number', 'like', "%{$search}%")
                        ->orWhere('invoice_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['supplier_id'], fn ($query, $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($filters['warehouse_id'], fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($filters['start_date'], fn ($query, $startDate) => $query->whereDate('receipt_date', '>=', $startDate))
            ->when($filters['end_date'], fn ($query, $endDate) => $query->whereDate('receipt_date', '<=', $endDate))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('purchase-receipt/index', [
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

        return Inertia::render('purchase-receipt/create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StorePurchaseReceiptRequest $request): RedirectResponse
    {
        $receipt = $this->service->store($request->validated());

        return redirect()
            ->route('purchase-receipts.show', $receipt)
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

        return Inertia::render('purchase-receipt/show', [
            'purchaseReceipt' => $purchaseReceipt,
        ]);
    }

    public function edit(PurchaseReceipt $purchaseReceipt): Response
    {
        $this->authorize('update', $purchaseReceipt);

        $purchaseReceipt->load([
            'items.product:id,name',
        ]);

        return Inertia::render('purchase-receipt/edit', [
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
            ->route('purchase-receipts.show', $purchaseReceipt)
            ->with('alert', Flash::success('Recebimento atualizado com sucesso.'));
    }

    public function destroy(PurchaseReceipt $purchaseReceipt): RedirectResponse
    {
        $this->authorize('delete', $purchaseReceipt);

        $this->service->delete($purchaseReceipt);

        return redirect()
            ->route('purchase-receipts.index')
            ->with('alert', Flash::success('Recebimento excluído com sucesso.'));
    }

    public function receive(PurchaseReceipt $purchaseReceipt): RedirectResponse
    {
        $this->authorize('receive', $purchaseReceipt);

        $this->service->receive($purchaseReceipt);

        return redirect()
            ->route('purchase-receipts.show', $purchaseReceipt)
            ->with('alert', Flash::success('Recebimento lançado com sucesso.'));
    }

    public function cancel(PurchaseReceipt $purchaseReceipt): RedirectResponse
    {
        $this->authorize('cancel', $purchaseReceipt);

        $this->service->cancel($purchaseReceipt);

        return redirect()
            ->route('purchase-receipts.show', $purchaseReceipt)
            ->with('alert', Flash::success('Recebimento cancelado com sucesso.'));
    }
}
