<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório individual de banco de horas</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório individual de banco de horas',
        'subtitle' => 'Evolução dos movimentos do período',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Funcionário' => $account['employee_name'] ?: '—',
            'Período' =>
                \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') .
                ' a ' .
                \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y'),
            'Saldo inicial do período' => $account['opening_balance_label'],
            'Saldo atual' => $account['current_balance_label'],
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 12%;">Data</th>
                <th style="width: 20%;">Tipo</th>
                <th style="width: 14%;">Movimento</th>
                <th style="width: 14%;">Saldo após</th>
                <th style="width: 40%;">Descrição</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3"><strong>Saldo inicial do período</strong></td>
                <td class="text-right"><strong>{{ $account['opening_balance_label'] }}</strong></td>
                <td>Acumulado anterior a {{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }}</td>
            </tr>

            @forelse ($account['entries'] as $entry)
                <tr>
                    <td>{{ $entry['occurred_on'] ?: '—' }}</td>
                    <td>{{ $entry['entry_type_label'] ?: '—' }}</td>
                    <td class="text-right">{{ $entry['minutes_label'] }}</td>
                    <td class="text-right">{{ $entry['running_balance_label'] }}</td>
                    <td>{{ $entry['description'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhum movimento encontrado no período.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>Saldo atual</strong></td>
                <td class="text-right"><strong>{{ $account['current_balance_label'] }}</strong></td>
                <td>—</td>
            </tr>
        </tfoot>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
