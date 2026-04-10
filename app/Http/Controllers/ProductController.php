<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitOfMeasure;
use App\Support\Audit\Audit;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function dashboard(): Response
    {
        $this->authorize('dashboard', Product::class);

        return Inertia::render('products/Dashboard', [
            'cards' => [
                [
                    'title' => 'Categorias',
                    'description' => 'Gerencie as classificações organizacionais dos produtos.',
                    'href' => '/product-categories',
                    'permission' => 'productCategories.viewAny',
                    'action_label' => 'Acessar categorias',
                    'icon' => 'folder-tree',
                ],
                [
                    'title' => 'Marcas',
                    'description' => 'Gerencie as marcas compartilhadas vinculáveis aos produtos.',
                    'href' => '/brands',
                    'permission' => 'brands.viewAny',
                    'action_label' => 'Acessar marcas',
                    'icon' => 'tags',
                ],
                [
                    'title' => 'Unidades de medida',
                    'description' => 'Gerencie as unidades utilizadas no cadastro dos produtos.',
                    'href' => '/unit-of-measures',
                    'permission' => 'unitOfMeasures.viewAny',
                    'action_label' => 'Acessar unidades',
                    'icon' => 'ruler',
                ],
                [
                    'title' => 'Produtos',
                    'description' => 'Gerencie o cadastro central de produtos compartilhados do tenant.',
                    'href' => '/products',
                    'permission' => 'products.viewAny',
                    'action_label' => 'Acessar produtos',
                    'icon' => 'box',
                ],
                [
                    'title' => 'Fornecedores',
                    'description' => 'Gerencie os fornecedores compartilhados usados no recebimento.',
                    'href' => '/suppliers',
                    'permission' => 'suppliers.viewAny',
                    'action_label' => 'Acessar fornecedores',
                    'icon' => 'building-2',
                ],
                [
                    'title' => 'Vínculos fornecedor x produto',
                    'description' => 'Gerencie os vínculos comerciais que sustentam o XML de compra.',
                    'href' => '/supplier-product-references',
                    'permission' => 'supplierProductReferences.viewAny',
                    'action_label' => 'Acessar vínculos',
                    'icon' => 'link-2',
                ],
            ],
        ]);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unitOfMeasure:id,name,symbol'])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%")
                    ->orWhere('ncm_code', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('purchase_description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'ncm_code' => $product->ncm_code,
                'purchase_description' => $product->purchase_description,
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
                'unit_of_measure' => $product->unitOfMeasure?->symbol,
                'description' => $product->description,
                'tracks_stock' => $product->tracks_stock,
                'is_active' => $product->is_active,
                'created_at' => $product->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Product::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'products-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unitOfMeasure:id,name,symbol'])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%")
                    ->orWhere('ncm_code', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('purchase_description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'SKU',
                'GTIN/EAN',
                'NCM',
                'Descrição de compra',
                'Categoria',
                'Marca',
                'Unidade',
                'Controla estoque',
                'Status',
                'Criado em',
            ], ';');

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->id,
                    $product->name,
                    $product->sku,
                    $product->barcode,
                    $product->ncm_code,
                    $product->purchase_description,
                    $product->category?->name,
                    $product->brand?->name,
                    $product->unitOfMeasure?->symbol,
                    $product->tracks_stock ? 'Sim' : 'Não',
                    $product->is_active ? 'Ativo' : 'Inativo',
                    $product->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unitOfMeasure:id,name,symbol'])
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%")
                    ->orWhere('ncm_code', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('purchase_description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'ncm_code' => $product->ncm_code,
                'purchase_description' => $product->purchase_description,
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
                'unit_of_measure' => $product->unitOfMeasure?->symbol,
                'tracks_stock' => $product->tracks_stock ? 'Sim' : 'Não',
                'status' => $product->is_active ? 'Ativo' : 'Inativo',
                'created_at' => $product->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.products-report', [
                'products' => $products,
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
                'Content-Disposition' => 'attachment; filename="products-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('products/Create', [
            'categories' => $this->categories($request->user()->tenant_id),
            'brands' => $this->brands($request->user()->tenant_id),
            'unitOfMeasures' => $this->unitOfMeasures($request->user()->tenant_id),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $product = Product::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'ncm_code' => $data['ncm_code'] ?? null,
                'product_category_id' => $data['product_category_id'],
                'unit_of_measure_id' => $data['unit_of_measure_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'description' => $data['description'] ?? null,
                'purchase_description' => $data['purchase_description'] ?? null,
                'tracks_stock' => $data['tracks_stock'],
                'is_active' => $data['is_active'],
            ]);

            Audit::event('products.created', $product, [
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'ncm_code' => $product->ncm_code,
                'product_category_id' => $product->product_category_id,
                'unit_of_measure_id' => $product->unit_of_measure_id,
                'brand_id' => $product->brand_id,
                'description' => $product->description,
                'purchase_description' => $product->purchase_description,
                'tracks_stock' => $product->tracks_stock,
                'is_active' => $product->is_active,
            ]);
        });

        return redirect()
            ->route('products.index')
            ->with('alert', Flash::success('Produto criado', 'O produto foi criado com sucesso.'));
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('products/Edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'ncm_code' => $product->ncm_code,
                'product_category_id' => $product->product_category_id,
                'unit_of_measure_id' => $product->unit_of_measure_id,
                'brand_id' => $product->brand_id,
                'description' => $product->description,
                'purchase_description' => $product->purchase_description,
                'tracks_stock' => $product->tracks_stock,
                'is_active' => $product->is_active,
            ],
            'categories' => $this->categories($request->user()->tenant_id),
            'brands' => $this->brands($request->user()->tenant_id),
            'unitOfMeasures' => $this->unitOfMeasures($request->user()->tenant_id),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $data = $request->validated();

            $before = [
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'ncm_code' => $product->ncm_code,
                'product_category_id' => $product->product_category_id,
                'unit_of_measure_id' => $product->unit_of_measure_id,
                'brand_id' => $product->brand_id,
                'description' => $product->description,
                'purchase_description' => $product->purchase_description,
                'tracks_stock' => $product->tracks_stock,
                'is_active' => $product->is_active,
            ];

            $product->update([
                'name' => $data['name'],
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'ncm_code' => $data['ncm_code'] ?? null,
                'product_category_id' => $data['product_category_id'],
                'unit_of_measure_id' => $data['unit_of_measure_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'description' => $data['description'] ?? null,
                'purchase_description' => $data['purchase_description'] ?? null,
                'tracks_stock' => $data['tracks_stock'],
                'is_active' => $data['is_active'],
            ]);

            $after = [
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'ncm_code' => $product->ncm_code,
                'product_category_id' => $product->product_category_id,
                'unit_of_measure_id' => $product->unit_of_measure_id,
                'brand_id' => $product->brand_id,
                'description' => $product->description,
                'purchase_description' => $product->purchase_description,
                'tracks_stock' => $product->tracks_stock,
                'is_active' => $product->is_active,
            ];

            Audit::event('products.updated', $product, [
                'before' => $before,
                'after' => $after,
            ]);
        });

        return redirect()
            ->route('products.index')
            ->with('alert', Flash::success('Produto atualizado', 'O produto foi atualizado com sucesso.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        DB::transaction(function () use ($product) {
            Audit::event('products.deleted', $product, [
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'ncm_code' => $product->ncm_code,
                'product_category_id' => $product->product_category_id,
                'unit_of_measure_id' => $product->unit_of_measure_id,
                'brand_id' => $product->brand_id,
                'description' => $product->description,
                'purchase_description' => $product->purchase_description,
                'tracks_stock' => $product->tracks_stock,
                'is_active' => $product->is_active,
            ]);

            $product->delete();
        });

        return redirect()
            ->route('products.index')
            ->with('alert', Flash::success('Produto excluído', 'O produto foi excluído com sucesso.'));
    }

    private function categories(int $tenantId): array
    {
        return ProductCategory::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ProductCategory $category) => [
                'value' => $category->id,
                'label' => $category->name,
            ])
            ->values()
            ->all();
    }

    private function brands(int $tenantId): array
    {
        return Brand::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Brand $brand) => [
                'value' => $brand->id,
                'label' => $brand->name,
            ])
            ->values()
            ->all();
    }

    private function unitOfMeasures(int $tenantId): array
    {
        return UnitOfMeasure::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'symbol'])
            ->map(fn (UnitOfMeasure $unitOfMeasure) => [
                'value' => $unitOfMeasure->id,
                'label' => $unitOfMeasure->name . ' (' . $unitOfMeasure->symbol . ')',
            ])
            ->values()
            ->all();
    }
}
