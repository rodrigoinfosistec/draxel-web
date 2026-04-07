<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Produtos</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Produtos',
        'subtitle' => 'Produtos compartilhados do tenant',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Todas as empresas',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Busca' => $filters['search'] ?? null,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>SKU</th>
                <th>Categoria</th>
                <th>Marca</th>
                <th>Unidade</th>
                <th>Estoque</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product['id'] }}</td>
                    <td>{{ $product['name'] }}</td>
                    <td>{{ $product['sku'] }}</td>
                    <td>{{ $product['category'] ?: '—' }}</td>
                    <td>{{ $product['brand'] ?: '—' }}</td>
                    <td>{{ $product['unit_of_measure'] ?: '—' }}</td>
                    <td>{{ $product['tracks_stock'] }}</td>
                    <td>{{ $product['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhum produto encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
