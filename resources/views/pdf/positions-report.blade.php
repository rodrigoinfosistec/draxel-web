<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Cargos</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Cargos',
        'subtitle' => 'Cargos cadastrados no grupo',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
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
                <th>Slug</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($positions as $position)
                <tr>
                    <td>{{ $position['id'] }}</td>
                    <td>{{ $position['slug'] }}</td>
                    <td>{{ $position['name'] }}</td>
                    <td>{{ $position['description'] ?: '—' }}</td>
                    <td>{{ $position['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhum cargo encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
