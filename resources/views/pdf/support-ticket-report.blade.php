<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Chamados</title>
    @include('pdf.partials.report-styles')
    <style>
        .muted {
            color: #6b7280;
        }

        .small {
            font-size: 8px;
        }
    </style>
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Chamados',
        'subtitle' => 'Chamados de suporte registrados no sistema',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Busca' => $filters['search'] ?? null,
            'Status' => $filters['status'] ?? null,
            'Data inicial' => $filters['date_from'] ?? null,
            'Data final' => $filters['date_to'] ?? null,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Assunto</th>
                <th>Status</th>
                <th>Aberto</th>
                <th>Solicitante</th>
                <th>Responsável</th>
                <th>Última interação</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr>
                    <td>{{ $ticket['id'] }}</td>
                    <td>{{ $ticket['code'] }}</td>
                    <td>{{ $ticket['subject'] }}</td>
                    <td>{{ $ticket['status'] }}</td>
                    <td>{{ $ticket['is_open'] }}</td>
                    <td>
                        {{ $ticket['creator_name'] ?? '—' }}
                        @if (!empty($ticket['creator_email']))
                            <div class="small muted">{{ $ticket['creator_email'] }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $ticket['assignee_name'] ?? '—' }}
                        @if (!empty($ticket['assignee_email']))
                            <div class="small muted">{{ $ticket['assignee_email'] }}</div>
                        @endif
                    </td>
                    <td>{{ $ticket['last_interaction_at'] ?? '—' }}</td>
                    <td>{{ $ticket['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Nenhum chamado encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
