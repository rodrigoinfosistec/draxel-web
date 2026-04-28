<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de pedidos</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de pedidos',
        'subtitle' => 'Relação dos pedidos do módulo Order',
    ])

    @include('pdf.partials.report-filters', [
        'filters' => [
            'Depósito' => $filters['warehouse_id'] ?: 'Todos',
            'Cliente' => $filters['client_id'] ?: 'Todos',
            'Status' => $filters['status'] ?: 'Todos',
            'Tipo' => $filters['type'] ?: 'Todos',
            'Data inicial' => $filters['start_date'] ?: '-',
            'Data final' => $filters['end_date'] ?: '-',
            'Busca' => $filters['search'] ?: '-',
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Número</th>
                <th>Status</th>
                <th>Tipo</th>
                <th>Depósito</th>
                <th>Cliente</th>
                <th>Documento</th>
                <th>Itens</th>
                <th>Qtd. Total</th>
                <th>Emitido em</th>
                <th>Criado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order['id'] }}</td>
                    <td>{{ $order['number'] }}</td>
                    <td>{{ $order['status_label'] }}</td>
                    <td>{{ $order['type_label'] }}</td>
                    <td>{{ $order['warehouse_name'] }}</td>
                    <td>{{ $order['client_name'] }}</td>
                    <td>{{ $order['client_document'] ?: '—' }}</td>
                    <td>{{ $order['items_count'] }}</td>
                    <td>{{ $order['products_total'] }}</td>
                    <td>{{ $order['issued_at'] }}</td>
                    <td>{{ $order['creator_name'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">Nenhum pedido encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
