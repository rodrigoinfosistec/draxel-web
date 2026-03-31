<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório geral de banco de horas</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório geral de banco de horas',
        'subtitle' => 'Demonstrativo resumido de saldos por funcionário',
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
            'Funcionários' => count($accounts),
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>Funcionário</th>
                <th>Saldo inicial do período</th>
                <th>Saldo atual</th>
                <th>Movimentos no período</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($accounts as $account)
                <tr>
                    <td>{{ $account['employee_name'] ?: '—' }}</td>
                    <td class="text-right">{{ $account['opening_balance_label'] }}</td>
                    <td class="text-right">{{ $account['current_balance_label'] }}</td>
                    <td class="text-center">{{ count($account['entries']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Nenhuma conta encontrada para o período informado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
