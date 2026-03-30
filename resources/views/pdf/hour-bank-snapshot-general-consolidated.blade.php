<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório geral consolidado de fechamento</title>
    @include('pdf.partials.report-styles')
    @php
        if (!function_exists('pdf_format_minutes_general_consolidated')) {
            function pdf_format_minutes_general_consolidated(int $minutes): string
            {
                $negative = $minutes < 0;
                $absoluteMinutes = abs($minutes);

                $hours = intdiv($absoluteMinutes, 60);
                $remainingMinutes = $absoluteMinutes % 60;

                $formatted = sprintf('%02d:%02d', $hours, $remainingMinutes);

                return $negative ? '-' . $formatted : $formatted;
            }
        }
    @endphp
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório geral consolidado de fechamento',
        'subtitle' => 'Demonstrativo geral após a consolidação',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Fechamento' => $snapshot->name,
            'Período' => $snapshot->period_start->format('d/m/Y') . ' a ' . $snapshot->period_end->format('d/m/Y'),
            'Funcionários incluídos' => $employees->count(),
            'Status' => $snapshot->status->label(),
            'Consolidado em' => $snapshot->consolidated_at?->format('d/m/Y H:i') ?: '—',
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 24%;">Funcionário</th>
                <th style="width: 10%;">Justificadas</th>
                <th style="width: 10%;">Atrasos</th>
                <th style="width: 10%;">Dispensa</th>
                <th style="width: 10%;">Extras</th>
                <th style="width: 8%;">Saldo</th>
                <th style="width: 14%;">Suspensão</th>
                <th style="width: 14%;">Falta</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td>
                        <strong>{{ $employee['employee_name'] }}</strong>
                        <br>
                        <span class="text-muted">
                            Matrícula: {{ $employee['employee_registration'] ?: '—' }}
                        </span>
                    </td>
                    <td class="text-right">
                        {{ pdf_format_minutes_general_consolidated((int) $employee['justified_minutes']) }}
                    </td>
                    <td class="text-right">
                        {{ pdf_format_minutes_general_consolidated((int) $employee['late_minutes']) }}
                    </td>
                    <td class="text-right">
                        {{ pdf_format_minutes_general_consolidated((int) $employee['dispensation_minutes']) }}
                    </td>
                    <td class="text-right">
                        {{ pdf_format_minutes_general_consolidated((int) $employee['extra_minutes']) }}
                    </td>
                    <td class="text-right">
                        {{ pdf_format_minutes_general_consolidated((int) $employee['balance_minutes']) }}
                    </td>
                    <td>
                        {{ $employee['suspension_dates_label'] }}
                    </td>
                    <td>
                        {{ $employee['absence_dates_label'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhum funcionário encontrado.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="text-right"><strong>Totais</strong></td>
                <td class="text-right">
                    <strong>{{ pdf_format_minutes_general_consolidated((int) $employees->sum('justified_minutes')) }}</strong>
                </td>
                <td class="text-right">
                    <strong>{{ pdf_format_minutes_general_consolidated((int) $employees->sum('late_minutes')) }}</strong>
                </td>
                <td class="text-right">
                    <strong>{{ pdf_format_minutes_general_consolidated((int) $employees->sum('dispensation_minutes')) }}</strong>
                </td>
                <td class="text-right">
                    <strong>{{ pdf_format_minutes_general_consolidated((int) $employees->sum('extra_minutes')) }}</strong>
                </td>
                <td class="text-right">
                    <strong>{{ pdf_format_minutes_general_consolidated((int) $employees->sum('balance_minutes')) }}</strong>
                </td>
                <td>—</td>
                <td>—</td>
            </tr>
        </tfoot>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
