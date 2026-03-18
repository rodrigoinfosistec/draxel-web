<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Registros de Ponto</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Registros de Ponto',
        'subtitle' => 'Registros cadastrados no módulo de ponto',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Busca' => $filters['search'] ?? null,
            'Data' => $filters['date'] ?? null,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Funcionário</th>
                <th>Origem</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Observações</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td>{{ $record['id'] }}</td>
                    <td>{{ $record['employee_name'] ?: '—' }}</td>
                    <td>{{ $record['source_type'] ?: '—' }}</td>
                    <td>{{ $record['date'] ?? '—' }}</td>
                    <td>{{ $record['time'] ?? '—' }}</td>
                    <td>{{ $record['notes'] ?: '—' }}</td>
                    <td>{{ $record['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nenhum registro encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
