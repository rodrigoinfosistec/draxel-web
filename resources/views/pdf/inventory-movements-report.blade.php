<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de movimentações de estoque</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de movimentações de estoque',
        'subtitle' => 'Histórico de entradas, saídas e ajustes por depósito',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Depósito' => $filters['warehouse_id'] ?? null,
            'Produto' => $filters['product_id'] ?? null,
            'Tipo' => $filters['type'] ?? null,
            'Data inicial' => $filters['start_date'] ?? null,
            'Data final' => $filters['end_date'] ?? null,
            'Busca' => $filters['search'] ?? null,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Depósito</th>
                <th>Produto</th>
                <th>Tipo</th>
                <th>Origem</th>
                <th>Qtd.</th>
                <th>Custo Unit.</th>
                <th>Referência</th>
                <th>Observação</th>
                <th>Data</th>
                <th>Usuário</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($movements as $movement)
                <tr>
                    <td>{{ $movement['id'] }}</td>
                    <td>{{ $movement['warehouse_name'] ?: '—' }}</td>
                    <td>{{ $movement['product_name'] ?: '—' }}</td>
                    <td>{{ $movement['type'] ?: '—' }}</td>
                    <td>{{ $movement['source_type'] ?: '—' }}</td>
                    <td>{{ $movement['quantity'] ?: '—' }}</td>
                    <td>{{ $movement['unit_cost'] ?: '—' }}</td>
                    <td>{{ $movement['reference'] ?: '—' }}</td>
                    <td>{{ $movement['notes'] ?: '—' }}</td>
                    <td>{{ $movement['moved_at'] ?: '—' }}</td>
                    <td>{{ $movement['user_name'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">Nenhuma movimentação encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
