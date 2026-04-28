<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Support\Audit\Audit;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $clients = Client::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('document', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%")
                    ->orWhere('notes', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'document' => $client->document,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'notes' => $client->notes,
                'created_at' => $client->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Client::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'clients-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $clients = Client::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('document', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%")
                    ->orWhere('notes', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($clients) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Documento',
                'E-mail',
                'Telefone',
                'Endereço',
                'Observações',
                'Criado em',
            ], ';');

            foreach ($clients as $client) {
                fputcsv($handle, [
                    $client->id,
                    $client->name,
                    $client->document,
                    $client->email,
                    $client->phone,
                    $client->address,
                    $client->notes,
                    $client->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Client::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $clients = Client::query()
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('document', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%")
                    ->orWhere('notes', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get()
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'document' => $client->document,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'notes' => $client->notes,
                'created_at' => $client->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.clients-report', [
                'clients' => $clients,
                'filters' => [
                    'search' => $search,
                ],
                'generatedAt' => now()->format('d/m/Y H:i:s'),
                'tenantName' => $request->user()->tenant?->name ?? 'Tenant',
                'companyName' => null,
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
                'Content-Disposition' => 'attachment; filename="clients-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('clients/Create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $client = Client::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'document' => $data['document'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            Audit::event('clients.created', $client, [
                'name' => $client->name,
                'document' => $client->document,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'notes' => $client->notes,
            ]);
        });

        return redirect()
            ->route('clients.index')
            ->with('alert', Flash::success('Cliente criado', 'O cliente foi criado com sucesso.'));
    }

    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('clients/Edit', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'document' => $client->document,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'notes' => $client->notes,
            ],
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        DB::transaction(function () use ($request, $client) {
            $data = $request->validated();

            $before = [
                'name' => $client->name,
                'document' => $client->document,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'notes' => $client->notes,
            ];

            $client->update([
                'name' => $data['name'],
                'document' => $data['document'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            Audit::event('clients.updated', $client, [
                'before' => $before,
                'after' => [
                    'name' => $client->name,
                    'document' => $client->document,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'address' => $client->address,
                    'notes' => $client->notes,
                ],
            ]);
        });

        return redirect()
            ->route('clients.index')
            ->with('alert', Flash::success('Cliente atualizado', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        DB::transaction(function () use ($client) {
            $snapshot = [
                'name' => $client->name,
                'document' => $client->document,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'notes' => $client->notes,
            ];

            Audit::event('clients.deleted', $client, $snapshot);

            $client->delete();
        });

        return redirect()
            ->route('clients.index')
            ->with('alert', Flash::success('Cliente removido', 'O cliente foi removido com sucesso.'));
    }
}
