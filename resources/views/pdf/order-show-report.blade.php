<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Pedido {{ $order['number'] }}</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Pedido ' . $order['number'],
        'subtitle' => 'Relatório individual do pedido',
    ])

    @include('pdf.partials.report-filters', [
        'filters' => [
            'Empresa' => $companyName,
            'Status' => $order['status_label'] ?: '—',
            'Tipo' => $order['type_label'] ?: '—',
            'Depósito' => $order['warehouse_name'] ?: '—',
            'Emitido em' => $order['issued_at'] ?: '—',
            'Gerado em' => $generatedAt,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th colspan="2">Dados do cliente</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 28%;">Cliente</td>
                <td>{{ $order['client_name'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Documento</td>
                <td>{{ $order['client_document'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>E-mail</td>
                <td>{{ $order['client_email'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Telefone</td>
                <td>{{ $order['client_phone'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Endereço</td>
                <td>{{ $order['client_address'] ?: '—' }}</td>
            </tr>
        </tbody>
    </table>

    <br>

    <table class="report-table">
        <thead>
            <tr>
                <th colspan="2">Dados do pedido</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 28%;">Número</td>
                <td>{{ $order['number'] }}</td>
            </tr>
            <tr>
                <td>Status</td>
                <td>{{ $order['status_label'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Tipo</td>
                <td>{{ $order['type_label'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Depósito</td>
                <td>{{ $order['warehouse_name'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Emitido em</td>
                <td>{{ $order['issued_at'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Criado por</td>
                <td>{{ $order['created_by'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Confirmado por</td>
                <td>{{ $order['confirmed_by'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Confirmado em</td>
                <td>{{ $order['confirmed_at'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Cancelado por</td>
                <td>{{ $order['cancelled_by'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Cancelado em</td>
                <td>{{ $order['cancelled_at'] ?: '—' }}</td>
            </tr>
            <tr>
                <td>Total de itens</td>
                <td>{{ $order['items_count'] }}</td>
            </tr>
            <tr>
                <td>Quantidade total</td>
                <td>{{ $order['products_total'] }}</td>
            </tr>
            <tr>
                <td>Observações</td>
                <td>{{ $order['notes'] ?: '—' }}</td>
            </tr>
        </tbody>
    </table>

    <br>

    <table class="report-table">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order['items'] as $item)
                <tr>
                    <td>{{ $item['product_name'] ?: '—' }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ $item['notes'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Nenhum item encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
