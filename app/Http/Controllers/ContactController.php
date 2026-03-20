<?php

namespace App\Http\Controllers;

use App\Enums\ContactItemType;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Support\Audit\Audit;
use App\Support\Flash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Contact::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $contacts = Contact::query()
            ->with('items')
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'description' => $contact->description,
                'items' => $contact->items->map(fn ($item) => [
                    'id' => $item->id,
                    'type' => $item->type?->value,
                    'type_label' => $item->type?->label(),
                    'value' => $item->value,
                    'label' => $item->label,
                ])->values(),
                'created_at' => $contact->created_at?->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('contacts/Index', [
            'contacts' => $contacts,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Contact::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();
        $filename = 'contacts-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $contacts = Contact::query()
            ->with('items')
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Nome',
                'Descrição',
                'Tipo',
                'Valor',
                'Rótulo',
                'Criado em',
            ], ';');

            foreach ($contacts as $contact) {
                foreach ($contact->items as $item) {
                    fputcsv($handle, [
                        $contact->id,
                        $contact->name,
                        $contact->description,
                        $item->type?->label(),
                        $item->value,
                        $item->label,
                        $contact->created_at?->format('d/m/Y H:i:s'),
                    ], ';');
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Contact::class);

        $tenantId = $request->user()->tenant_id;
        $search = $request->string('search')->toString();

        $contacts = Contact::query()
            ->with('items')
            ->where('tenant_id', $tenantId)
            ->when($search, fn ($query) => $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            }))
            ->latest()
            ->get()
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'description' => $contact->description,
                'items' => $contact->items->map(fn ($item) => [
                    'type' => $item->type?->label(),
                    'value' => $item->value,
                    'label' => $item->label,
                ])->values(),
                'created_at' => $contact->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.contacts-report', [
                'contacts' => $contacts,
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
                'Content-Disposition' => 'attachment; filename="contacts-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Contact::class);

        return Inertia::render('contacts/Create', [
            'itemTypes' => ContactItemType::options(),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        DB::transaction(function () use ($request, $tenantId) {
            $data = $request->validated();

            $contact = Contact::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $contact->items()->create([
                    'tenant_id' => $tenantId,
                    'type' => $item['type'],
                    'value' => $item['value'],
                    'label' => $item['label'] ?? null,
                    'sort_order' => $item['sort_order'],
                ]);
            }

            Audit::event('contacts.created', $contact, [
                'name' => $contact->name,
                'description' => $contact->description,
                'items_count' => $contact->items()->count(),
            ]);
        });

        return redirect()
            ->route('contacts.index')
            ->with('alert', Flash::success('Contato criado', 'O contato foi criado com sucesso.'));
    }

    public function edit(Contact $contact): Response
    {
        $this->authorize('update', $contact);

        $contact->load('items');

        return Inertia::render('contacts/Edit', [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'description' => $contact->description,
                'items' => $contact->items
                    ->map(fn ($item, int $index) => [
                        'type' => $item->type?->value,
                        'value' => $item->value,
                        'label' => $item->label,
                        'sort_order' => $index,
                    ])
                    ->values(),
            ],
            'itemTypes' => ContactItemType::options(),
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        DB::transaction(function () use ($request, $contact) {
            $data = $request->validated();

            $before = [
                'name' => $contact->name,
                'description' => $contact->description,
            ];

            $contact->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $contact->items()->delete();

            foreach ($data['items'] as $item) {
                $contact->items()->create([
                    'tenant_id' => $contact->tenant_id,
                    'type' => $item['type'],
                    'value' => $item['value'],
                    'label' => $item['label'] ?? null,
                    'sort_order' => $item['sort_order'],
                ]);
            }

            Audit::event('contacts.updated', $contact, [
                'before' => $before,
                'after' => [
                    'name' => $contact->name,
                    'description' => $contact->description,
                ],
            ]);
        });

        return redirect()
            ->route('contacts.index')
            ->with('alert', Flash::success('Contato atualizado', 'As alterações foram salvas com sucesso.'));
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        DB::transaction(function () use ($contact) {
            $snapshot = [
                'name' => $contact->name,
                'description' => $contact->description,
            ];

            Audit::event('contacts.deleted', $contact, $snapshot);

            $contact->delete();
        });

        return redirect()
            ->route('contacts.index')
            ->with('alert', Flash::success('Contato removido', 'O contato foi removido com sucesso.'));
    }
}
