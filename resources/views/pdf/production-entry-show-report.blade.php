<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Entrada de produção {{ $entry['number'] }}</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Entrada de produção ' . $entry['number'],
        'subtitle' => 'Detalhamento do lançamento de produção',
    ])

    <table class="report-table" style="margin-bottom: 14px;">
        <tbody>
            <tr>
                <th style="width: 18%;">Número</th>
                <td>{{ $entry['number'] }}</td>
                <th style="width: 18%;">Data</th>
                <td>{{ $entry['entry_date'] }}</td>
            </tr>
            <tr>
                <th>Depósito</th>
                <td>{{ $entry['warehouse_name'] }}</td>
                <th>Status</th>
                <td>{{ $entry['status_label'] }}</td>
            </tr>
            <tr>
                <th>Criado por</th>
                <td>{{ $entry['created_by'] ?: '—' }}</td>
                <th>Lançado por</th>
                <td>{{ $entry['posted_by'] ?: '—' }}</td>
            </tr>
            <tr>
                <th>Data do lançamento</th>
                <td>{{ $entry['posted_at'] ?: '—' }}</td>
                <th>Cancelado por</th>
                <td>{{ $entry['cancelled_by'] ?: '—' }}</td>
            </tr>
            <tr>
                <th>Data do cancelamento</th>
                <td>{{ $entry['cancelled_at'] ?: '—' }}</td>
                <th>Motivo do cancelamento</th>
                <td>{{ $entry['cancel_reason'] ?: '—' }}</td>
            </tr>
            <tr>
                <th>Observação</th>
                <td colspan="3">{{ $entry['notes'] ?: '—' }}</td>
            </tr>
        </tbody>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Custo Unitário</th>
                <th>Custo Total</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entry['items'] as $item)
                <tr>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ $item['unit_cost'] !== '' ? $item['unit_cost'] : '—' }}</td>
                    <td>{{ $item['total_cost'] !== '' ? $item['total_cost'] : '—' }}</td>
                    <td>{{ $item['notes'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Nenhum item encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
