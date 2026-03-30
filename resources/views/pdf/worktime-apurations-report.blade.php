<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de apuração de ponto</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de apuração de ponto',
        'subtitle' => 'Demonstrativo diário da apuração antes do fechamento',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Período' =>
                \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') .
                ' a ' .
                \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y'),
            'Previsto' => $totals['expected_hours'] ?? null,
            'Trabalhado' => $totals['worked_hours'] ?? null,
            'Atrasos' => $totals['delay_hours'] ?? null,
            'Dispensas' => $totals['dispensation_hours'] ?? null,
            'Extras' => $totals['overtime_hours'] ?? null,
            'DSR/Feriado' => $totals['dsr_worked_hours'] ?? null,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 14%;">Funcionário</th>
                <th style="width: 7%;">Data</th>
                <th style="width: 11%;">Jornada prevista</th>
                <th style="width: 8%;">Trabalhado</th>
                <th style="width: 8%;">Atrasos</th>
                <th style="width: 8%;">Dispensa</th>
                <th style="width: 8%;">Extra</th>
                <th style="width: 9%;">DSR/Feriado</th>
                <th style="width: 6%;">Registros</th>
                <th style="width: 9%;">Status</th>
                <th style="width: 12%;">Observações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($days as $day)
                <tr>
                    <td>{{ $day['employee_name'] }}</td>
                    <td>{{ $day['date'] }}</td>
                    <td class="text-right">{{ $day['expected_hours'] }}</td>
                    <td class="text-right">{{ $day['worked_hours'] }}</td>
                    <td class="text-right">{{ $day['delay_hours'] }}</td>
                    <td class="text-right">{{ $day['dispensation_hours'] }}</td>
                    <td class="text-right">{{ $day['overtime_hours'] }}</td>
                    <td class="text-right">{{ $day['dsr_worked_hours'] }}</td>
                    <td class="text-center">{{ $day['records_count'] }}</td>
                    <td>{{ $day['status_label'] }}</td>
                    <td>{{ $day['notes'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">Nenhum registro encontrado para o período informado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
