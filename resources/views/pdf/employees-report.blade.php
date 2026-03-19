<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Funcionários</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Funcionários',
        'subtitle' => 'Funcionários cadastrados no grupo',
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
                <th>CPF</th>
                <th>Matrícula</th>
                <th>Departamento</th>
                <th>Cargo</th>
                <th>Status</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td>{{ $employee['id'] }}</td>
                    <td>{{ $employee['name'] }}</td>
                    <td>{{ $employee['cpf'] }}</td>
                    <td>{{ $employee['registration'] }}</td>
                    <td>{{ $employee['department'] ?: '—' }}</td>
                    <td>{{ $employee['position'] ?: '—' }}</td>
                    <td>{{ $employee['status'] }}</td>
                    <td>{{ $employee['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhum funcionário encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
