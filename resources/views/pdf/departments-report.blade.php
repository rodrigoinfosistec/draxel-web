<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Departamentos</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Departamentos',
        'subtitle' => 'Departamentos cadastrados no tenant',
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
                <th>Nome</th>
                <th>Slug</th>
                <th>Descrição</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($departments as $department)
                <tr>
                    <td>{{ $department['id'] }}</td>
                    <td>{{ $department['name'] }}</td>
                    <td>{{ $department['slug'] }}</td>
                    <td>{{ $department['description'] ?: '—' }}</td>
                    <td>{{ $department['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhum departamento encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
