<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de entradas de produção</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de entradas de produção',
        'subtitle' => 'Lançamentos de produção da empresa em contexto',
    ])

    @include('pdf.partials.report-filters', [
        'filters' => [
            'Depósito' => $filters['warehouse_id'] ?: 'Todos',
            'Data inicial' => $filters['start_date'] ?: 'Não informada',
            'Data final' => $filters['end_date'] ?: 'Não informada',
            'Status' => $filters['status'] ?: 'Todos',
            'Busca' => $filters['search'] ?: 'Não informada',
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Número</th>
                <th>Data</th>
                <th>Depósito</th>
                <th>Status</th>
                <th>Itens</th>
                <th>Qtd. Total</th>
                <th>Criado por</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td>{{ $entry['id'] }}</td>
                    <td>{{ $entry['number'] }}</td>
                    <td>{{ $entry['entry_date'] }}</td>
                    <td>{{ $entry['warehouse_name'] }}</td>
                    <td>{{ $entry['status_label'] }}</td>
                    <td>{{ $entry['items_count'] }}</td>
                    <td>{{ $entry['total_quantity'] }}</td>
                    <td>{{ $entry['created_by'] }}</td>
                    <td>{{ $entry['notes'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Nenhuma entrada de produção encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
