<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de posição por depósito</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de posição por depósito',
        'subtitle' => 'Saldo atual dos produtos em cada depósito',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Depósito' => $filters['warehouse_id'] ?? null,
            'Busca' => $filters['search'] ?? null,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Depósito</th>
                <th>Produto</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($positions as $position)
                <tr>
                    <td>{{ $position['id'] }}</td>
                    <td>{{ $position['warehouse_name'] ?: '—' }}</td>
                    <td>{{ $position['product_name'] ?: '—' }}</td>
                    <td>{{ $position['quantity'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Nenhuma posição encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
