<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Product;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Order\Enums\OrderType;
use App\Modules\Order\Http\Requests\StoreOrderRequest;
use App\Modules\Order\Http\Requests\UpdateOrderRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Support\CompanyContext;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $clientId = trim((string) $request->string('client_id')->value());
        $status = trim((string) $request->string('status')->value());
        $type = trim((string) $request->string('type')->value());
        $startDate = trim((string) $request->string('start_date')->value());
        $endDate = trim((string) $request->string('end_date')->value());
        $search = trim((string) $request->string('search')->value());

        $filters = [
            'warehouse_id' => $warehouseId,
            'client_id' => $clientId,
            'status' => $status,
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'search' => $search,
        ];

        $orders = Order::query()
            ->with(['warehouse:id,name', 'client:id,name,document', 'creator:id,name'])
            ->withCount('items')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->filter($filters)
            ->latest('issued_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'type' => $order->type?->value,
                'type_label' => $order->type?->label(),
                'status' => $order->status?->value,
                'status_label' => $order->status?->label(),
                'warehouse_name' => $order->warehouse?->name,
                'client_name' => $order->client?->name,
                'client_document' => $order->client?->document,
                'destination_name' => $order->destination_name,
                'items_count' => $order->items_count,
                'products_total' => (float) $order->items()->sum('quantity'),
                'issued_at' => $order->issued_at?->format('d/m/Y H:i'),
                'creator_name' => $order->creator?->name,
            ]);

        return Inertia::render('order/orders/Index', [
            'orders' => $orders,
            'filters' => $filters,
            'warehouses' => $this->warehouseOptions($tenantId, $companyId),
            'clients' => $this->clientOptions($tenantId),
            'types' => OrderType::formOptions(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('export', Order::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $clientId = trim((string) $request->string('client_id')->value());
        $status = trim((string) $request->string('status')->value());
        $type = trim((string) $request->string('type')->value());
        $startDate = trim((string) $request->string('start_date')->value());
        $endDate = trim((string) $request->string('end_date')->value());
        $search = trim((string) $request->string('search')->value());

        $filename = 'orders-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $orders = Order::query()
            ->with(['warehouse:id,name', 'client:id,name,document', 'creator:id,name'])
            ->withCount('items')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->filter([
                'warehouse_id' => $warehouseId,
                'client_id' => $clientId,
                'status' => $status,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
            ])
            ->latest('issued_at')
            ->latest('id')
            ->get();

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Número',
                'Status',
                'Tipo',
                'Depósito',
                'Cliente',
                'Documento',
                'Itens',
                'Quantidade Total',
                'Emitido em',
                'Criado por',
            ], ';');

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->id,
                    $order->number,
                    $order->status?->label(),
                    $order->type?->label(),
                    $order->warehouse?->name,
                    $order->client?->name,
                    $order->client?->document,
                    $order->items()->count(),
                    number_format((float) $order->items()->sum('quantity'), 3, ',', '.'),
                    $order->issued_at?->format('d/m/Y H:i:s'),
                    $order->creator?->name,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('export', Order::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        $warehouseId = trim((string) $request->string('warehouse_id')->value());
        $clientId = trim((string) $request->string('client_id')->value());
        $status = trim((string) $request->string('status')->value());
        $type = trim((string) $request->string('type')->value());
        $startDate = trim((string) $request->string('start_date')->value());
        $endDate = trim((string) $request->string('end_date')->value());
        $search = trim((string) $request->string('search')->value());

        $orders = Order::query()
            ->with(['warehouse:id,name', 'client:id,name,document', 'creator:id,name'])
            ->withCount('items')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->filter([
                'warehouse_id' => $warehouseId,
                'client_id' => $clientId,
                'status' => $status,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
            ])
            ->latest('issued_at')
            ->latest('id')
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'status_label' => $order->status?->label(),
                'type_label' => $order->type?->label(),
                'warehouse_name' => $order->warehouse?->name,
                'client_name' => $order->client?->name,
                'client_document' => $order->client?->document,
                'items_count' => $order->items()->count(),
                'products_total' => number_format((float) $order->items()->sum('quantity'), 3, ',', '.'),
                'issued_at' => $order->issued_at?->format('d/m/Y H:i:s'),
                'creator_name' => $order->creator?->name,
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.orders-report', [
                'orders' => $orders,
                'filters' => [
                    'warehouse_id' => $warehouseId,
                    'client_id' => $clientId,
                    'status' => $status,
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
                'Content-Disposition' => 'attachment; filename="orders-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Order::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        return Inertia::render('order/orders/Create', [
            'warehouses' => $this->warehouseOptions($tenantId, $companyId),
            'clients' => $this->clientOptions($tenantId),
            'products' => $this->productOptions($tenantId),
            'types' => OrderType::formOptions(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->service->create(
            data: $request->validated(),
            user: $request->user(),
            companyId: CompanyContext::id(),
        );

        return redirect()
            ->route('order.orders.show', $order)
            ->with('alert', Flash::success(
                'Pedido criado',
                'O pedido foi criado com sucesso.'
            ));
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);
        $this->ensureContext($request, $order);

        $order->load([
            'warehouse:id,name',
            'client:id,name,document,email,phone',
            'items.product:id,name',
            'creator:id,name',
            'confirmer:id,name',
            'canceller:id,name',
        ]);

        return Inertia::render('order/orders/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'type' => $order->type?->value,
                'type_label' => $order->type?->label(),
                'status' => $order->status?->value,
                'status_label' => $order->status?->label(),
                'warehouse' => $order->warehouse ? [
                    'id' => $order->warehouse->id,
                    'name' => $order->warehouse->name,
                ] : null,
                'warehouse_id' => $order->warehouse_id,
                'warehouse_name' => $order->warehouse?->name,
                'client' => $order->client ? [
                    'id' => $order->client->id,
                    'name' => $order->client->name,
                    'document' => $order->client->document,
                    'email' => $order->client->email,
                    'phone' => $order->client->phone,
                ] : null,
                'client_id' => $order->client_id,
                'client_name' => $order->client?->name,
                'client_document' => $order->client?->document,
                'destination_name' => $order->destination_name,
                'notes' => $order->notes,
                'issued_at' => $order->issued_at?->format('d/m/Y H:i'),
                'confirmed_at' => $order->confirmed_at?->format('d/m/Y H:i'),
                'cancelled_at' => $order->cancelled_at?->format('d/m/Y H:i'),
                'created_by' => $order->creator?->name,
                'confirmed_by' => $order->confirmer?->name,
                'cancelled_by' => $order->canceller?->name,
                'items_count' => $order->items->count(),
                'products_total' => (float) $order->items->sum('quantity'),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'quantity' => (float) $item->quantity,
                    'notes' => $item->notes,
                ])->values(),
                'can_edit' => $order->isDraft(),
                'can_delete' => $order->isDraft(),
                'can_confirm' => $order->isDraft(),
                'can_cancel' => $order->isDraft(),
            ],
        ]);
    }

    public function edit(Request $request, Order $order): Response
    {
        $this->authorize('update', $order);
        $this->ensureContext($request, $order);

        abort_unless($order->isDraft(), 403);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        $order->load(['items.product:id,name']);

        return Inertia::render('order/orders/Edit', [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'warehouse_id' => $order->warehouse_id,
                'client_id' => $order->client_id,
                'type' => $order->type?->value,
                'destination_name' => $order->destination_name,
                'notes' => $order->notes,
                'status' => $order->status?->value,
                'issued_at' => $order->issued_at?->format('Y-m-d\TH:i'),
                'items' => $order->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'notes' => $item->notes,
                ])->values(),
            ],
            'warehouses' => $this->warehouseOptions($tenantId, $companyId),
            'clients' => $this->clientOptions($tenantId),
            'products' => $this->productOptions($tenantId),
            'types' => OrderType::formOptions(),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        $this->ensureContext($request, $order);

        $order = $this->service->update($order, $request->validated());

        return redirect()
            ->route('order.orders.show', $order)
            ->with('alert', Flash::success(
                'Pedido atualizado',
                'O pedido foi atualizado com sucesso.'
            ));
    }

    public function confirm(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('confirm', $order);
        $this->ensureContext($request, $order);

        $order = $this->service->confirm($order, $request->user());

        return redirect()
            ->route('order.orders.show', $order)
            ->with('alert', Flash::success(
                'Pedido confirmado',
                'O pedido foi confirmado e o estoque foi movimentado com sucesso.'
            ));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);
        $this->ensureContext($request, $order);

        $order = $this->service->cancel($order, $request->user());

        return redirect()
            ->route('order.orders.show', $order)
            ->with('alert', Flash::success(
                'Pedido cancelado',
                'O pedido foi cancelado com sucesso.'
            ));
    }

    public function destroy(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);
        $this->ensureContext($request, $order);

        $this->service->delete($order);

        return redirect()
            ->route('order.orders.index')
            ->with('alert', Flash::success(
                'Pedido excluído',
                'O pedido foi excluído com sucesso.'
            ));
    }

    protected function ensureContext(Request $request, Order $order): void
    {
        abort_unless(
            $order->tenant_id === $request->user()->tenant_id
            && $order->company_id === CompanyContext::id(),
            404
        );
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

    protected function clientOptions(int $tenantId)
    {
        return Client::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'document'])
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'document' => $client->document,
            ])
            ->values();
    }

    protected function productOptions(int $tenantId)
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
            ])
            ->values();
    }

    public function exportOrderPdf(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $this->ensureContext($request, $order);

        $order->load([
            'warehouse:id,name',
            'client:id,name,document,phone',
            'items.product:id,name',
        ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.order-show-report', [
                'order' => [
                    'number' => $order->number,
                    'status_label' => $order->status?->label(),
                    'type_label' => $order->type?->label(),
                    'warehouse_name' => $order->warehouse?->name,
                    'client_name' => $order->client?->name,
                    'client_document' => $order->client?->document,
                    'client_phone' => $order->client?->phone,
                    'notes' => $order->notes,
                    'issued_at' => $order->issued_at?->format('d/m/Y H:i'),
                    'items_count' => $order->items->count(),
                    'products_total' => number_format((float) $order->items->sum('quantity'), 3, ',', '.'),
                    'items' => $order->items->map(fn ($item) => [
                        'product_name' => $item->product?->name,
                        'quantity' => number_format((float) $item->quantity, 3, ',', '.'),
                        'notes' => $item->notes,
                    ])->values(),
                ],
                'generatedAt' => now()->format('d/m/Y H:i'),
                'tenantName' => $request->user()->tenant?->name ?? 'Tenant',
                'companyName' => CompanyContext::current()?->name ?? 'Empresa',
            ])
            ->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans Mono', 'normal');

        $canvas->page_text(
            520,
            810,
            '{PAGE_NUM}/{PAGE_COUNT}',
            $font,
            8,
            [0.42, 0.45, 0.5]
        );

        return response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="pedido-' . $order->number . '.pdf"',
            ]
        );
    }

}
