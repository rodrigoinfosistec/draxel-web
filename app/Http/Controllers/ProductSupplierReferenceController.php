<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductSupplierReferenceRequest;
use App\Http\Requests\UpdateProductSupplierReferenceRequest;
use App\Models\Product;
use App\Models\ProductSupplierReference;
use App\Models\Supplier;
use App\Support\Audit\Audit;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductSupplierReferenceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductSupplierReference::class);

        $tenantId = $request->user()->tenant_id;
        $search = trim($request->string('search')->toString());

        $references = ProductSupplierReference::query()
            ->with([
                'supplier:id,name,trade_name,document',
                'product:id,name,sku,barcode',
            ])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('supplier_product_code', 'ilike', "%{$search}%")
                    ->orWhere('supplier_product_description', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%")
                    ->orWhere('unit', 'ilike', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('trade_name', 'ilike', "%{$search}%")
                        ->orWhere('document', 'ilike', "%{$search}%"))
                    ->orWhereHas('product', fn ($productQuery) => $productQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('sku', 'ilike', "%{$search}%")
                        ->orWhere('barcode', 'ilike', "%{$search}%"));
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ProductSupplierReference $reference) => [
                'id' => $reference->id,
                'supplier' => $reference->supplier?->name,
                'supplier_trade_name' => $reference->supplier?->trade_name,
                'product' => $reference->product?->name,
                'product_sku' => $reference->product?->sku,
                'supplier_product_code' => $reference->supplier_product_code,
                'supplier_product_description' => $reference->supplier_product_description,
                'barcode' => $reference->barcode,
                'unit' => $reference->unit,
                'is_active' => $reference->is_active,
                'created_at' => $reference->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('supplier-product-references/Index', [
            'references' => $references,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', ProductSupplierReference::class);

        $tenantId = $request->user()->tenant_id;
        $search = trim($request->string('search')->toString());
        $filename = 'supplier-product-references-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $references = ProductSupplierReference::query()
            ->with([
                'supplier:id,name,trade_name,document',
                'product:id,name,sku,barcode',
            ])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('supplier_product_code', 'ilike', "%{$search}%")
                    ->orWhere('supplier_product_description', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%")
                    ->orWhere('unit', 'ilike', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('trade_name', 'ilike', "%{$search}%")
                        ->orWhere('document', 'ilike', "%{$search}%"))
                    ->orWhereHas('product', fn ($productQuery) => $productQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('sku', 'ilike', "%{$search}%")
                        ->orWhere('barcode', 'ilike', "%{$search}%"));
            }))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($references) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Fornecedor',
                'Produto',
                'SKU',
                'Código do fornecedor',
                'Descrição do fornecedor',
                'GTIN/EAN',
                'Unidade',
                'Status',
                'Criado em',
            ], ';');

            foreach ($references as $reference) {
                fputcsv($handle, [
                    $reference->id,
                    $reference->supplier?->name,
                    $reference->product?->name,
                    $reference->product?->sku,
                    $reference->supplier_product_code,
                    $reference->supplier_product_description,
                    $reference->barcode,
                    $reference->unit,
                    $reference->is_active ? 'Ativo' : 'Inativo',
                    $reference->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', ProductSupplierReference::class);

        $tenantId = $request->user()->tenant_id;
        $search = trim($request->string('search')->toString());

        $references = ProductSupplierReference::query()
            ->with([
                'supplier:id,name,trade_name,document',
                'product:id,name,sku,barcode',
            ])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('supplier_product_code', 'ilike', "%{$search}%")
                    ->orWhere('supplier_product_description', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%")
                    ->orWhere('unit', 'ilike', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('trade_name', 'ilike', "%{$search}%")
                        ->orWhere('document', 'ilike', "%{$search}%"))
                    ->orWhereHas('product', fn ($productQuery) => $productQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('sku', 'ilike', "%{$search}%")
                        ->orWhere('barcode', 'ilike', "%{$search}%"));
            }))
            ->latest()
            ->get()
            ->map(fn (ProductSupplierReference $reference) => [
                'id' => $reference->id,
                'supplier' => $reference->supplier?->name,
                'product' => $reference->product?->name,
                'product_sku' => $reference->product?->sku,
                'supplier_product_code' => $reference->supplier_product_code,
                'supplier_product_description' => $reference->supplier_product_description,
                'barcode' => $reference->barcode,
                'unit' => $reference->unit,
                'status' => $reference->is_active ? 'Ativo' : 'Inativo',
                'created_at' => $reference->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.product-supplier-references-report', [
                'references' => $references,
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
                'Content-Disposition' => 'attachment; filename="supplier-product-references-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ProductSupplierReference::class);

        return Inertia::render('supplier-product-references/Create', [
            'suppliers' => $this->suppliers($request->user()->tenant_id),
            'products' => $this->products($request->user()->tenant_id),
        ]);
    }

    public function store(StoreProductSupplierReferenceRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $reference = ProductSupplierReference::create([
                'tenant_id' => $tenantId,
                ...$request->validated(),
            ]);

            Audit::event('supplierProductReferences.created', $reference, $reference->toArray());
        });

        return redirect()
            ->route('supplier-product-references.index')
            ->with('alert', Flash::success('Vínculo criado', 'O vínculo fornecedor x produto foi criado com sucesso.'));
    }

    public function edit(Request $request, ProductSupplierReference $supplierProductReference): Response
    {
        $this->authorize('update', $supplierProductReference);

        return Inertia::render('supplier-product-references/Edit', [
            'reference' => [
                'id' => $supplierProductReference->id,
                'supplier_id' => $supplierProductReference->supplier_id,
                'product_id' => $supplierProductReference->product_id,
                'supplier_product_code' => $supplierProductReference->supplier_product_code,
                'supplier_product_description' => $supplierProductReference->supplier_product_description,
                'barcode' => $supplierProductReference->barcode,
                'unit' => $supplierProductReference->unit,
                'is_active' => $supplierProductReference->is_active,
            ],
            'suppliers' => $this->suppliers($request->user()->tenant_id),
            'products' => $this->products($request->user()->tenant_id),
        ]);
    }

    public function update(UpdateProductSupplierReferenceRequest $request, ProductSupplierReference $supplierProductReference): RedirectResponse
    {
        DB::transaction(function () use ($request, $supplierProductReference) {
            $before = $supplierProductReference->toArray();

            $supplierProductReference->update($request->validated());

            Audit::event('supplierProductReferences.updated', $supplierProductReference, [
                'before' => $before,
                'after' => $supplierProductReference->fresh()->toArray(),
            ]);
        });

        return redirect()
            ->route('supplier-product-references.index')
            ->with('alert', Flash::success('Vínculo atualizado', 'O vínculo fornecedor x produto foi atualizado com sucesso.'));
    }

    public function destroy(ProductSupplierReference $supplierProductReference): RedirectResponse
    {
        $this->authorize('delete', $supplierProductReference);

        DB::transaction(function () use ($supplierProductReference) {
            Audit::event('supplierProductReferences.deleted', $supplierProductReference, $supplierProductReference->toArray());

            $supplierProductReference->delete();
        });

        return redirect()
            ->route('supplier-product-references.index')
            ->with('alert', Flash::success('Vínculo excluído', 'O vínculo fornecedor x produto foi excluído com sucesso.'));
    }

    private function suppliers(int $tenantId): array
    {
        return Supplier::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'trade_name', 'document'])
            ->map(fn (Supplier $supplier) => [
                'value' => $supplier->id,
                'label' => $supplier->name . ($supplier->trade_name ? ' (' . $supplier->trade_name . ')' : ''),
            ])
            ->values()
            ->all();
    }

    private function products(int $tenantId): array
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn (Product $product) => [
                'value' => $product->id,
                'label' => $product->name . ' (' . $product->sku . ')',
            ])
            ->values()
            ->all();
    }
}
