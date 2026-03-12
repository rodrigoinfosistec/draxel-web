<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Chamados</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 16mm 16mm 20mm 16mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 0;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .header-table,
        .report-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-table td,
        .footer-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .header-left {
            width: 60%;
            padding-right: 10mm;
        }

        .header-right {
            width: 40%;
            text-align: right;
        }

        .report-title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .report-subtitle {
            font-size: 10px;
            color: #6b7280;
            line-height: 1.35;
        }

        .tenant-name {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
            line-height: 1.2;
            word-break: break-word;
        }

        .company-name {
            font-size: 10px;
            color: #6b7280;
            line-height: 1.35;
            word-break: break-word;
        }

        .filters {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 14px;
        }

        .filters-row {
            margin-bottom: 3px;
        }

        .filters-row:last-child {
            margin-bottom: 0;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }

        .report-table th {
            background: #f3f4f6;
            font-size: 9px;
            font-weight: 700;
        }

        .report-table td {
            font-size: 9px;
        }

        .muted {
            color: #6b7280;
        }

        .small {
            font-size: 8px;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -10mm;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 4mm;
        }

        .footer-left {
            text-align: left;
            font-size: 10px;
            line-height: 1.2;
        }

        .footer-right {
            text-align: right;
        }

        .footer-logo {
            width: 13px;
            height: 13px;
            vertical-align: -2px;
            margin-right: 5px;
        }

        .footer-brand {
            font-weight: 700;
            font-size: 10px;
            color: #374151;
        }

        .footer-link {
            color: #6b7280;
            font-size: 10px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="report-title">Relatório de Chamados</div>
                    <div class="report-subtitle">Chamados de suporte registrados no sistema</div>
                    <div class="report-subtitle">Gerado em {{ $generatedAt }}</div>
                </td>
                <td class="header-right">
                    <div class="tenant-name">{{ $tenantName ?? 'Tenant' }}</div>
                    <div class="company-name">{{ $companyName ?? 'Empresa' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="filters">
        <div class="filters-row"><strong>Busca:</strong> {{ $filters['search'] ?: '—' }}</div>
        <div class="filters-row"><strong>Status:</strong> {{ $filters['status'] ?: '—' }}</div>
        <div class="filters-row"><strong>Data inicial:</strong> {{ $filters['date_from'] ?: '—' }}</div>
        <div class="filters-row"><strong>Data final:</strong> {{ $filters['date_to'] ?: '—' }}</div>
    </div>

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

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    <img src="{{ public_path('favicon.png') }}" alt="Draxel" class="footer-logo">
                    <span class="footer-brand">Draxel Soluções</span>
                    —
                    <a href="https://draxel.com.br" target="_blank" rel="noopener noreferrer"
                        class="footer-link">https://draxel.com.br</a>
                </td>
                <td class="footer-right"></td>
            </tr>
        </table>
    </div>
</body>

</html>
