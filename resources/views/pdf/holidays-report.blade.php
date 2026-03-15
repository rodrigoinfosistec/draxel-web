<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Feriados</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Feriados',
        'subtitle' => 'Feriados cadastrados no tenant',
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
                <th>Data</th>
                <th>Descrição</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($holidays as $holiday)
                <tr>
                    <td>{{ $holiday['id'] }}</td>
                    <td>{{ $holiday['name'] }}</td>
                    <td>{{ $holiday['date'] }}</td>
                    <td>{{ $holiday['description'] ?: '—' }}</td>
                    <td>{{ $holiday['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhum feriado encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
