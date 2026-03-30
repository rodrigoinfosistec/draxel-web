<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório individual de fechamento</title>
    @include('pdf.partials.report-styles')
    <style>
        .signature-box {
            margin-top: 28px;
            border: 1px solid #374151;
            padding: 12px 14px 18px 14px;
        }

        .signature-law {
            font-size: 9px;
            line-height: 1.45;
            color: #374151;
            margin-bottom: 34px;
        }

        .signature-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signature-grid td {
            border: 0;
            vertical-align: top;
            padding: 0;
        }

        .signature-left {
            width: 44%;
            padding-right: 24px;
        }

        .signature-right {
            width: 56%;
            text-align: center;
        }

        .signature-line-left {
            width: 280px;
            border-top: 1px solid #374151;
            height: 1px;
            margin-top: 40px;
            margin-bottom: 6px;
        }

        .signature-line-right {
            width: 380px;
            border-top: 1px solid #374151;
            height: 1px;
            margin: 40px auto 6px 80px;
        }

        .signature-label {
            font-size: 10px;
            font-style: italic;
            color: #374151;
        }

        .signature-name {
            margin-top: 6px;
            font-size: 11px;
            font-weight: bold;
            color: #111827;
        }

        .signature-recognition {
            font-size: 10px;
            font-style: italic;
            color: #374151;
            margin-top: 4px;
        }

        .text-danger {
            color: #b91c1c;
            font-weight: bold;
        }

        .text-info {
            color: #92400e;
        }

        .text-muted {
            color: #6b7280;
        }
    </style>
</head>

<body>
    @php
        if (!function_exists('pdf_format_minutes')) {
            function pdf_format_minutes(int $minutes): string
            {
                $negative = $minutes < 0;
                $absoluteMinutes = abs($minutes);

                $hours = intdiv($absoluteMinutes, 60);
                $remainingMinutes = $absoluteMinutes % 60;

                $formatted = sprintf('%02d:%02d', $hours, $remainingMinutes);

                return $negative ? '-' . $formatted : $formatted;
            }
        }

        if (!function_exists('pdf_normalize_weekday_label')) {
            function pdf_normalize_weekday_label(?string $weekdayLabel): string
            {
                $value = mb_strtoupper(trim((string) $weekdayLabel));

                return match ($value) {
                    'SEG' => 'SEG',
                    'TER' => 'TER',
                    'QUA' => 'QUA',
                    'QUI' => 'QUI',
                    'SEX' => 'SEX',
                    'SÁ', 'SAB', 'SÁB' => 'SÁB',
                    'DOM' => 'DOM',
                    default => $value,
                };
            }
        }

        if (!function_exists('pdf_build_expected_schedule')) {
            function pdf_build_expected_schedule($day): string
            {
                $parts = [];

                if ($day->expected_start_time && $day->expected_end_time) {
                    $parts[] = substr($day->expected_start_time, 0, 5) . ' - ' . substr($day->expected_end_time, 0, 5);
                }

                if ($day->expected_break_duration) {
                    $parts[] = 'Int. ' . substr($day->expected_break_duration, 0, 5);
                }

                return count($parts) ? implode(' | ', $parts) : '—';
            }
        }

        if (!function_exists('pdf_build_records_label')) {
            function pdf_build_records_label($day, string $expectedSchedule): string
            {
                $records = collect($day->records ?? [])
                    ->filter()
                    ->values();

                if ($records->isNotEmpty()) {
                    return $records->implode(' | ');
                }

                if (filled($day->notes)) {
                    return $day->notes;
                }

                if ($expectedSchedule === '—') {
                    return 'DSR';
                }

                if ((bool) $day->has_divergence) {
                    return 'Ausência';
                }

                return '—';
            }
        }

        if (!function_exists('pdf_build_records_class')) {
            function pdf_build_records_class($day, string $expectedSchedule): string
            {
                $records = collect($day->records ?? [])
                    ->filter()
                    ->values();

                if ($records->isNotEmpty()) {
                    return '';
                }

                if (filled($day->notes)) {
                    return 'text-info';
                }

                if ($expectedSchedule === '—') {
                    return 'text-muted';
                }

                if ((bool) $day->has_divergence) {
                    return 'text-danger';
                }

                return 'text-muted';
            }
        }
    @endphp

    @include('pdf.partials.report-header', [
        'title' => 'Relatório individual de fechamento',
        'subtitle' => 'Demonstrativo individual do fechamento de banco de horas',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Fechamento' => $snapshot->id . ' - ' . $snapshot->name,
            'Período' => $snapshot->period_start->format('d/m/Y') . ' a ' . $snapshot->period_end->format('d/m/Y'),
            'Funcionário' => $employee->employee_name,
            'Matrícula' => $employee->employee_registration ?: '—',
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 10%;">Data</th>
                <th style="width: 7%;">Dia</th>
                <th style="width: 18%;">Jornada esperada</th>
                <th style="width: 19%;">Registros</th>
                <th style="width: 9%;">Justificadas</th>
                <th style="width: 9%;">Atrasos</th>
                <th style="width: 9%;">Dispensa</th>
                <th style="width: 9%;">Extras</th>
                <th style="width: 10%;">DSR/Feriado</th>
                <th style="width: 10%;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($days as $day)
                @php
                    $expectedSchedule = pdf_build_expected_schedule($day);
                    $recordsLabel = pdf_build_records_label($day, $expectedSchedule);
                    $recordsClass = pdf_build_records_class($day, $expectedSchedule);
                @endphp

                <tr>
                    <td>{{ $day->work_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-center">{{ pdf_normalize_weekday_label($day->weekday_label) }}</td>
                    <td>{{ $expectedSchedule }}</td>
                    <td class="{{ $recordsClass }}">{{ $recordsLabel }}</td>
                    <td class="text-right">{{ pdf_format_minutes((int) $day->justified_minutes) }}</td>
                    <td class="text-right">{{ pdf_format_minutes((int) $day->late_minutes) }}</td>
                    <td class="text-right">{{ pdf_format_minutes((int) $day->suspension_minutes) }}</td>
                    <td class="text-right">{{ pdf_format_minutes((int) $day->extra_minutes) }}</td>
                    <td class="text-right">{{ pdf_format_minutes((int) $day->dsr_worked_minutes) }}</td>
                    <td class="text-right">{{ pdf_format_minutes((int) $day->balance_minutes) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">Nenhum dia encontrado para este fechamento.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>Totais</strong></td>
                <td class="text-right"><strong>{{ pdf_format_minutes((int) $employee->justified_minutes) }}</strong>
                </td>
                <td class="text-right"><strong>{{ pdf_format_minutes((int) $employee->late_minutes) }}</strong></td>
                <td class="text-right"><strong>{{ pdf_format_minutes((int) $employee->suspension_minutes) }}</strong>
                </td>
                <td class="text-right"><strong>{{ pdf_format_minutes((int) $employee->extra_minutes) }}</strong></td>
                <td class="text-right"><strong>{{ pdf_format_minutes((int) $employee->dsr_worked_minutes) }}</strong>
                </td>
                <td class="text-right"><strong>{{ pdf_format_minutes((int) $employee->balance_minutes) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-box">
        <div class="signature-law">
            De conformidade com a port. MTb Nº 3.626 de 13 de Novembro de 1991 Art 13, este Cartão de Ponto substitui,
            para todos os efeitos legais, o quadro de Horário de Trabalho, inclusive o de menores.
        </div>

        <table class="signature-grid">
            <tr>
                <td class="signature-left">
                    <div class="signature-line-left"></div>
                    <div class="signature-label">Local e Data</div>
                </td>
                <td class="signature-right">
                    <div class="signature-line-right"></div>
                    <div class="signature-recognition">Reconheço a exatidão destas informações e dou fé,</div>
                    <div class="signature-name">{{ $employee->employee_name }}</div>
                </td>
            </tr>
        </table>
    </div>

    @include('pdf.partials.report-footer')
</body>

</html>
