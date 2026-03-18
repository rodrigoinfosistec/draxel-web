<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Apuração de Ponto</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Apuração de Ponto',
        'subtitle' => 'Apuração diária do período selecionado',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Data inicial' => $filters['start_date'] ?? null,
            'Data final' => $filters['end_date'] ?? null,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>Funcionário</th>
                <th>Data</th>
                <th>Previsto</th>
                <th>Trabalhado</th>
                <th>Atraso</th>
                <th>Saída antecipada</th>
                <th>Extra</th>
                <th>Ausência</th>
                <th>Registros</th>
                <th>Status</th>
                <th>Observações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($days as $day)
                <tr>
                    <td>{{ $day['employee_name'] }}</td>
                    <td>{{ $day['date'] }}</td>
                    <td>{{ $day['expected_minutes'] }}</td>
                    <td>{{ $day['worked_minutes'] }}</td>
                    <td>{{ $day['delay_minutes'] }}</td>
                    <td>{{ $day['early_exit_minutes'] }}</td>
                    <td>{{ $day['overtime_minutes'] }}</td>
                    <td>{{ $day['absence_minutes'] }}</td>
                    <td>{{ $day['records_count'] }}</td>
                    <td>{{ $day['status'] }}</td>
                    <td>{{ $day['notes'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">Nenhuma apuração encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
