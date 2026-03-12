<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\CompanyContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $audits = $this->filteredQuery($request)
            ->with([
                'user:id,name,email',
                'company:id,name',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AuditLog $audit) => $this->transformAudit($audit));

        return Inertia::render('audit/Index', [
            'audits' => $audits,
            'filters' => $this->filters($request),
            'events' => $this->eventOptions($request->user()->tenant_id),
            'users' => $this->userOptions($request->user()->tenant_id),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $filename = 'audit-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $audits = $this->filteredQuery($request)
            ->with([
                'user:id,name,email',
                'company:id,name',
            ])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($audits) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Evento',
                'Usuario',
                'Email do usuario',
                'Empresa',
                'Subject Type',
                'Subject ID',
                'Rota',
                'Metodo',
                'IP',
                'Data',
                'Properties',
            ], ';');

            foreach ($audits as $audit) {
                fputcsv($handle, [
                    $audit->id,
                    $audit->event,
                    $audit->user?->name,
                    $audit->user?->email,
                    $audit->company?->name,
                    $audit->subject_type,
                    $audit->subject_id,
                    $audit->route,
                    $audit->method,
                    $audit->ip,
                    $audit->created_at?->format('d/m/Y H:i:s'),
                    $audit->properties ? json_encode($audit->properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', AuditLog::class);

        $audits = $this->filteredQuery($request)
            ->with([
                'user:id,name,email',
                'company:id,name',
            ])
            ->latest()
            ->get()
            ->map(fn (AuditLog $audit) => $this->transformAudit($audit));

        $pdf = Pdf::setOption([
                'isPhpEnabled' => false,
            ])
            ->loadView('pdf.audit-report', [
                'audits' => $audits,
                'filters' => $this->filters($request),
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
                'Content-Disposition' => 'attachment; filename="audit-' . now()->format('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    protected function filteredQuery(Request $request): Builder
    {
        $tenantId = $request->user()->tenant_id;
        $companyId = CompanyContext::id();

        return AuditLog::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('event', 'ilike', "%{$search}%")
                        ->orWhere('subject_type', 'ilike', "%{$search}%")
                        ->orWhere('subject_id', 'ilike', "%{$search}%")
                        ->orWhere('route', 'ilike', "%{$search}%")
                        ->orWhere('ip', 'ilike', "%{$search}%");
                });
            })
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->string('event')->toString()))
            ->when($request->integer('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->string('date_from')->toString()))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->string('date_to')->toString()));
    }

    protected function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'event' => $request->string('event')->toString(),
            'user_id' => $request->integer('user_id') ?: null,
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];
    }

    protected function eventOptions(int $tenantId)
    {
        return AuditLog::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', CompanyContext::id())
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->values();
    }

    protected function userOptions(int $tenantId)
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);
    }

    protected function transformAudit(AuditLog $audit): array
    {
        return [
            'id' => $audit->id,
            'event' => $audit->event,
            'subject_type' => $audit->subject_type,
            'subject_id' => $audit->subject_id,
            'route' => $audit->route,
            'method' => $audit->method,
            'ip' => $audit->ip,
            'user_agent' => $audit->user_agent,
            'created_at' => $audit->created_at?->format('d/m/Y H:i:s'),
            'created_at_iso' => $audit->created_at?->toIso8601String(),
            'user' => $audit->user ? [
                'id' => $audit->user->id,
                'name' => $audit->user->name,
                'email' => $audit->user->email,
            ] : null,
            'company' => $audit->company ? [
                'id' => $audit->company->id,
                'name' => $audit->company->name,
            ] : null,
            'properties' => $audit->properties,
        ];
    }
}
