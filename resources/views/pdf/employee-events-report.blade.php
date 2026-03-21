<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Eventos de Funcionário</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Eventos de Funcionário',
        'subtitle' => 'Períodos e justificativas cadastradas',
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
                <th>Funcionário</th>
                <th>Tipo</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Observações</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                <tr>
                    <td>{{ $event['employee_name'] }}</td>
                    <td>{{ $event['event_type'] }}</td>
                    <td>{{ $event['starts_at'] }}</td>
                    <td>{{ $event['ends_at'] }}</td>
                    <td>{{ $event['notes'] ?: '—' }}</td>
                    <td>{{ $event['created_at'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nenhum evento encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
