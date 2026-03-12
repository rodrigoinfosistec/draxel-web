<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Auditoria</title>
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
                    <div class="report-title">Relatório de Auditoria</div>
                    <div class="report-subtitle">Eventos e alterações registradas no sistema</div>
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
        <div class="filters-row"><strong>Evento:</strong> {{ $filters['event'] ?: '—' }}</div>
        <div class="filters-row"><strong>Usuário:</strong> {{ $filters['user_id'] ?: '—' }}</div>
        <div class="filters-row"><strong>Data inicial:</strong> {{ $filters['date_from'] ?: '—' }}</div>
        <div class="filters-row"><strong>Data final:</strong> {{ $filters['date_to'] ?: '—' }}</div>
    </div>

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
