<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Auditoria</title>
    @include('pdf.partials.report-styles')
    <style>
        .muted {
            color: #6b7280;
        }

        .small {
            font-size: 8px;
        }

        .pre {
            white-space: pre-wrap;
            word-break: break-word;
        }

        .col-id {
            width: 6%;
        }

        .col-event {
            width: 18%;
        }

        .col-user {
            width: 18%;
        }

        .col-company {
            width: 14%;
        }

        .col-ip {
            width: 12%;
        }

        .col-date {
            width: 14%;
        }

        .col-properties {
            width: 18%;
        }
    </style>
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Auditoria',
        'subtitle' => 'Eventos e alterações registradas no sistema',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Busca' => $filters['search'] ?? null,
            'Evento' => $filters['event'] ?? null,
            'Usuário' => $filters['user_id'] ?? null,
            'Data inicial' => $filters['date_from'] ?? null,
            'Data final' => $filters['date_to'] ?? null,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-event">Evento</th>
                <th class="col-user">Usuário</th>
                <th class="col-company">Empresa</th>
                <th class="col-ip">IP</th>
                <th class="col-date">Data</th>
                <th class="col-properties">Properties</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($audits as $audit)
                <tr>
                    <td>{{ $audit['id'] }}</td>
                    <td>{{ $audit['event'] }}</td>
                    <td>
                        {{ $audit['user']['name'] ?? '—' }}
                        @if (!empty($audit['user']['email']))
                            <div class="small muted">{{ $audit['user']['email'] }}</div>
                        @endif
                    </td>
                    <td>{{ $audit['company']['name'] ?? '—' }}</td>
                    <td>{{ $audit['ip'] ?? '—' }}</td>
                    <td>{{ $audit['created_at'] ?? '—' }}</td>
                    <td class="pre small">
                        {{ json_encode($audit['properties'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nenhum registro encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
