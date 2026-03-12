<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Funções</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Funções',
        'subtitle' => 'Funções cadastradas no tenant',
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
                <th>Status</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($roles as $role)
                <tr>
                    <td>{{ $role['id'] }}</td>
                    <td>{{ $role['slug'] }}</td>
                    <td>{{ $role['name'] }}</td>
                    <td>{{ $role['description'] ?: '—' }}</td>
                    <td>{{ $role['status'] }}</td>
                    <td>{{ $role['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nenhuma função encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
