<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
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

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Supplier::class);

        $tenantId = $request->user()->tenant_id;
        $search = trim($request->string('search')->toString());

        $suppliers = Supplier::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('trade_name', 'ilike', "%{$search}%")
                    ->orWhere('document', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'trade_name' => $supplier->trade_name,
                'document' => $supplier->document,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'mobile' => $supplier->mobile,
                'city' => $supplier->city,
                'state' => $supplier->state,
                'is_active' => $supplier->is_active,
                'created_at' => $supplier->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $tenantId = $request->user()->tenant_id;
        $search = trim($request->string('search')->toString());
        $filename = 'suppliers-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $suppliers = Supplier::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('trade_name', 'ilike', "%{$search}%")
                    ->orWhere('document', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($suppliers) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Razão social',
                'Nome fantasia',
                'Documento',
                'E-mail',
                'Telefone',
                'Celular',
                'Cidade',
                'UF',
                'Status',
                'Criado em',
            ], ';');

            foreach ($suppliers as $supplier) {
                fputcsv($handle, [
                    $supplier->id,
                    $supplier->name,
                    $supplier->trade_name,
                    $supplier->document,
                    $supplier->email,
                    $supplier->phone,
                    $supplier->mobile,
                    $supplier->city,
                    $supplier->state,
                    $supplier->is_active ? 'Ativo' : 'Inativo',
                    $supplier->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $tenantId = $request->user()->tenant_id;
        $search = trim($request->string('search')->toString());

        $suppliers = Supplier::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('trade_name', 'ilike', "%{$search}%")
                    ->orWhere('document', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get()
            ->map(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'trade_name' => $supplier->trade_name,
                'document' => $supplier->document,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'mobile' => $supplier->mobile,
                'city' => $supplier->city,
                'state' => $supplier->state,
                'status' => $supplier->is_active ? 'Ativo' : 'Inativo',
                'created_at' => $supplier->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.suppliers-report', [
                'suppliers' => $suppliers,
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
                'Content-Disposition' => 'attachment; filename="suppliers-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Supplier::class);

        return Inertia::render('suppliers/Create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $supplier = Supplier::create([
                'tenant_id' => $tenantId,
                ...$request->validated(),
            ]);

            Audit::event('suppliers.created', $supplier, $supplier->toArray());
        });

        return redirect()
            ->route('suppliers.index')
            ->with('alert', Flash::success('Fornecedor criado', 'O fornecedor foi criado com sucesso.'));
    }

    public function edit(Supplier $supplier): Response
    {
        $this->authorize('update', $supplier);

        return Inertia::render('suppliers/Edit', [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'trade_name' => $supplier->trade_name,
                'document' => $supplier->document,
                'state_registration' => $supplier->state_registration,
                'municipal_registration' => $supplier->municipal_registration,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'mobile' => $supplier->mobile,
                'zip_code' => $supplier->zip_code,
                'street' => $supplier->street,
                'number' => $supplier->number,
                'complement' => $supplier->complement,
                'district' => $supplier->district,
                'city' => $supplier->city,
                'state' => $supplier->state,
                'notes' => $supplier->notes,
                'is_active' => $supplier->is_active,
            ],
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        DB::transaction(function () use ($request, $supplier) {
            $before = $supplier->toArray();

            $supplier->update($request->validated());

            Audit::event('suppliers.updated', $supplier, [
                'before' => $before,
                'after' => $supplier->fresh()->toArray(),
            ]);
        });

        return redirect()
            ->route('suppliers.index')
            ->with('alert', Flash::success('Fornecedor atualizado', 'O fornecedor foi atualizado com sucesso.'));
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        DB::transaction(function () use ($supplier) {
            Audit::event('suppliers.deleted', $supplier, $supplier->toArray());

            $supplier->delete();
        });

        return redirect()
            ->route('suppliers.index')
            ->with('alert', Flash::success('Fornecedor excluído', 'O fornecedor foi excluído com sucesso.'));
    }
}
