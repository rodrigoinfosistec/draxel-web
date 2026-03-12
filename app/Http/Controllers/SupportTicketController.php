<?php

namespace App\Http\Controllers;

use App\Enums\SupportTicketStatus;
use App\Http\Requests\ReplySupportTicketRequest;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Http\Requests\UpdateSupportTicketStatusRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Notifications\SupportTicketUpdatedNotification;
use App\Support\Audit\Audit;
use App\Support\CompanyContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SupportTicket::class);

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $tickets = $this->filteredQuery($request)
            ->with([
                'creator:id,name,email',
                'assignee:id,name,email',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SupportTicket $ticket) => [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
                'status_label' => $ticket->status->label(),
                'is_open' => $ticket->is_open,
                'created_at' => $ticket->created_at?->format('d/m/Y H:i'),
                'last_interaction_at' => $ticket->last_interaction_at?->format('d/m/Y H:i'),
                'creator' => $ticket->creator ? [
                    'id' => $ticket->creator->id,
                    'name' => $ticket->creator->name,
                    'email' => $ticket->creator->email,
                ] : null,
                'assignee' => $ticket->assignee ? [
                    'id' => $ticket->assignee->id,
                    'name' => $ticket->assignee->name,
                    'email' => $ticket->assignee->email,
                ] : null,
            ]);

        return Inertia::render('support-tickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statuses' => collect(SupportTicketStatus::cases())->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SupportTicket::class);

        return Inertia::render('support-tickets/Create');
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $ticket = DB::transaction(function () use ($request) {
            $ticket = SupportTicket::create([
                'tenant_id' => $request->user()->tenant_id,
                'company_id' => CompanyContext::id(),
                'created_by' => $request->user()->id,
                'code' => $this->generateCode(),
                'subject' => $request->validated('subject'),
                'description' => $request->validated('description'),
                'status' => SupportTicketStatus::OPEN,
                'is_open' => true,
                'last_interaction_at' => now(),
            ]);

            SupportTicketMessage::create([
                'tenant_id' => $request->user()->tenant_id,
                'company_id' => CompanyContext::id(),
                'support_ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'message' => $ticket->description,
                'is_internal' => false,
            ]);

            Audit::event('support_tickets.created', $ticket, [
                'code' => $ticket->code,
                'subject' => $ticket->subject,
                'status' => $ticket->status->value,
            ]);

            return $ticket;
        });

        $ticket->load('creator');

        $ticket->creator?->notify(
            new SupportTicketUpdatedNotification($ticket, 'created')
        );

        return redirect()->route('support-tickets.show', $ticket);
    }

    public function show(SupportTicket $supportTicket): Response
    {
        $this->authorize('view', $supportTicket);

        $supportTicket->load([
            'creator:id,name,email',
            'assignee:id,name,email',
            'messages.user:id,name,email',
        ]);

        return Inertia::render('support-tickets/Show', [
            'ticket' => [
                'id' => $supportTicket->id,
                'code' => $supportTicket->code,
                'subject' => $supportTicket->subject,
                'description' => $supportTicket->description,
                'status' => $supportTicket->status->value,
                'status_label' => $supportTicket->status->label(),
                'is_open' => $supportTicket->is_open,
                'created_at' => $supportTicket->created_at?->format('d/m/Y H:i'),
                'creator' => $supportTicket->creator ? [
                    'id' => $supportTicket->creator->id,
                    'name' => $supportTicket->creator->name,
                    'email' => $supportTicket->creator->email,
                ] : null,
                'messages' => $supportTicket->messages->map(fn ($message) => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'is_internal' => $message->is_internal,
                    'created_at' => $message->created_at?->format('d/m/Y H:i'),
                    'user' => $message->user ? [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'email' => $message->user->email,
                    ] : null,
                ]),
            ],
            'statuses' => collect(SupportTicketStatus::cases())->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function reply(ReplySupportTicketRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        $payload = DB::transaction(function () use ($request, $supportTicket) {
            $message = SupportTicketMessage::create([
                'tenant_id' => $request->user()->tenant_id,
                'company_id' => CompanyContext::id(),
                'support_ticket_id' => $supportTicket->id,
                'user_id' => $request->user()->id,
                'message' => $request->validated('message'),
                'is_internal' => (bool) $request->validated('is_internal', false),
            ]);

            $supportTicket->update([
                'last_interaction_at' => now(),
                'status' => $message->is_internal
                    ? $supportTicket->status
                    : SupportTicketStatus::WAITING_CUSTOMER,
                'is_open' => true,
                'closed_at' => null,
            ]);

            Audit::event('support_tickets.replied', $supportTicket, [
                'message_id' => $message->id,
                'is_internal' => $message->is_internal,
            ]);

            return [$message, $supportTicket->fresh(['creator'])];
        });

        [$message, $ticket] = $payload;

        if (! $message->is_internal) {
            $ticket->creator?->notify(
                new SupportTicketUpdatedNotification($ticket, 'replied', $message->message)
            );
        }

        return redirect()->route('support-tickets.show', $supportTicket);
    }

    public function updateStatus(UpdateSupportTicketStatusRequest $request, SupportTicket $supportTicket): RedirectResponse
    {
        $status = SupportTicketStatus::from($request->validated('status'));

        $supportTicket->update([
            'status' => $status,
            'is_open' => $status->isOpen(),
            'closed_at' => $status->isOpen() ? null : now(),
            'last_interaction_at' => now(),
        ]);

        Audit::event('support_tickets.status_changed', $supportTicket, [
            'status' => $status->value,
        ]);

        $supportTicket->load('creator');

        $supportTicket->creator?->notify(
            new SupportTicketUpdatedNotification($supportTicket, 'status_changed')
        );

        return redirect()->route('support-tickets.show', $supportTicket);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $filename = 'support-tickets-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $tickets = $this->filteredQuery($request)
            ->with([
                'creator:id,name,email',
                'assignee:id,name,email',
            ])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($tickets) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Codigo',
                'Assunto',
                'Status',
                'Aberto',
                'Solicitante',
                'Email do solicitante',
                'Responsavel',
                'Email do responsavel',
                'Ultima interacao',
                'Criado em',
            ], ';');

            foreach ($tickets as $ticket) {
                fputcsv($handle, [
                    $ticket->id,
                    $ticket->code,
                    $ticket->subject,
                    $ticket->status->label(),
                    $ticket->is_open ? 'Sim' : 'Nao',
                    $ticket->creator?->name,
                    $ticket->creator?->email,
                    $ticket->assignee?->name,
                    $ticket->assignee?->email,
                    $ticket->last_interaction_at?->format('d/m/Y H:i:s'),
                    $ticket->created_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', SupportTicket::class);

        $tickets = $this->filteredQuery($request)
            ->with([
                'creator:id,name,email',
                'assignee:id,name,email',
            ])
            ->latest()
            ->get()
            ->map(fn (SupportTicket $ticket) => [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'subject' => $ticket->subject,
                'status' => $ticket->status->label(),
                'is_open' => $ticket->is_open ? 'Sim' : 'Não',
                'creator_name' => $ticket->creator?->name,
                'creator_email' => $ticket->creator?->email,
                'assignee_name' => $ticket->assignee?->name,
                'assignee_email' => $ticket->assignee?->email,
                'last_interaction_at' => $ticket->last_interaction_at?->format('d/m/Y H:i:s'),
                'created_at' => $ticket->created_at?->format('d/m/Y H:i:s'),
            ]);

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.support-ticket-report', [
                'tickets' => $tickets,
                'filters' => [
                    'search' => $request->string('search')->toString(),
                    'status' => $request->string('status')->toString(),
                    'date_from' => $request->string('date_from')->toString(),
                    'date_to' => $request->string('date_to')->toString(),
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
                'Content-Disposition' => 'attachment; filename="support-tickets-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    protected function filteredQuery(Request $request)
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        return SupportTicket::query()
            ->when(
                ! $request->user()->hasPermission('support.manageAll'),
                fn ($query) => $query->where('created_by', $request->user()->id)
            )
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('code', 'ilike', "%{$search}%")
                        ->orWhere('subject', 'ilike', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo));
    }

    protected function generateCode(): string
    {
        $lastId = (SupportTicket::withoutGlobalScopes()->max('id') ?? 0) + 1;

        return 'SUP-' . now()->format('Y') . '-' . str_pad((string) $lastId, 6, '0', STR_PAD_LEFT);
    }
}
