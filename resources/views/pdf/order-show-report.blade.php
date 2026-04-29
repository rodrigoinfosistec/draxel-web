<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Pedido {{ $order['number'] }}</title>
    @include('pdf.partials.report-styles')

    <style>
        .compact-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .compact-grid td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
            font-size: 10px;
            vertical-align: top;
        }

        .compact-label {
            width: 18%;
            font-weight: bold;
            color: #374151;
            background: #f9fafb;
        }

        .compact-value {
            width: 32%;
        }

        .section-title {
            margin: 10px 0 5px;
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }

        .compact-note {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            margin-bottom: 10px;
            font-size: 10px;
            line-height: 1.35;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
            font-size: 10px;
            vertical-align: top;
        }

        .items-table th {
            background: #f3f4f6;
            color: #111827;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .summary-line {
            margin-top: 6px;
            font-size: 10px;
            color: #374151;
        }
    </style>
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Pedido ' . $order['number'],
        'subtitle' => 'Relatório individual do pedido',
    ])

    <table class="compact-grid">
        <tbody>
            <tr>
                <td class="compact-label">Empresa</td>
                <td class="compact-value">{{ $companyName }}</td>

                <td class="compact-label">Pedido</td>
                <td class="compact-value">{{ $order['number'] }}</td>
            </tr>

            <tr>
                <td class="compact-label">Data</td>
                <td class="compact-value">{{ $order['issued_at'] ?: '—' }}</td>

                <td class="compact-label">Status</td>
                <td class="compact-value">{{ $order['status_label'] ?: '—' }}</td>
            </tr>

            <tr>
                <td class="compact-label">Tipo</td>
                <td class="compact-value">{{ $order['type_label'] ?: '—' }}</td>

                <td class="compact-label">Depósito</td>
                <td class="compact-value">{{ $order['warehouse_name'] ?: '—' }}</td>
            </tr>

            <tr>
                <td class="compact-label">Cliente</td>
                <td class="compact-value">{{ $order['client_name'] ?: '—' }}</td>

                <td class="compact-label">Documento</td>
                <td class="compact-value">{{ $order['client_document'] ?: '—' }}</td>
            </tr>

            <tr>
                <td class="compact-label">Telefone</td>
                <td class="compact-value">{{ $order['client_phone'] ?: '—' }}</td>

                <td class="compact-label">Gerado em</td>
                <td class="compact-value">{{ $generatedAt }}</td>
            </tr>
        </tbody>
    </table>

    @if ($order['notes'])
        <div class="section-title">Observação</div>
        <div class="compact-note">
            {{ $order['notes'] }}
        </div>
    @endif

    <div class="section-title">Produtos</div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 58%;">Produto</th>
                <th style="width: 14%;" class="text-right">Quantidade</th>
                <th style="width: 28%;">Observação</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($order['items'] as $item)
                <tr>
                    <td>{{ $item['product_name'] ?: '—' }}</td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    <td>{{ $item['notes'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Nenhum item encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-line">
        Total de itens: {{ $order['items_count'] }} |
        Quantidade total: {{ $order['products_total'] }}
    </div>
</body>

</html>
