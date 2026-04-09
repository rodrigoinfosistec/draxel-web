<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de depósitos</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de depósitos',
        'subtitle' => 'Depósitos cadastrados na empresa em contexto',
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
                <th>Código</th>
                <th>Descrição</th>
                <th>Ativo</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($warehouses as $warehouse)
                <tr>
                    <td>{{ $warehouse['id'] }}</td>
                    <td>{{ $warehouse['name'] }}</td>
                    <td>{{ $warehouse['code'] ?: '—' }}</td>
                    <td>{{ $warehouse['description'] ?: '—' }}</td>
                    <td>{{ $warehouse['is_active'] }}</td>
                    <td>{{ $warehouse['created_at'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nenhum depósito encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
