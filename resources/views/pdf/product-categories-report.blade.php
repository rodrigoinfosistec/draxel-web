<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Categorias de Produto</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Categorias de Produto',
        'subtitle' => 'Categorias compartilhadas do tenant',
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
                <th>Descrição</th>
                <th>Status</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($productCategories as $productCategory)
                <tr>
                    <td>{{ $productCategory['id'] }}</td>
                    <td>{{ $productCategory['name'] }}</td>
                    <td>{{ $productCategory['description'] ?: '—' }}</td>
                    <td>{{ $productCategory['status'] }}</td>
                    <td>{{ $productCategory['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhuma categoria encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
