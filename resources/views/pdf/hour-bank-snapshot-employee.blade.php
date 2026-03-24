<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório individual de fechamento</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 12mm 16mm 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 0;
        }

        .header {
            margin-bottom: 14px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 10px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 11px;
            color: #4b5563;
            margin-bottom: 2px;
        }

        .meta {
            margin-top: 8px;
            font-size: 10px;
        }

        .meta strong {
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
        }

        td {
            font-size: 9px;
        }

        .totals td {
            font-weight: bold;
            background: #f9fafb;
        }

        .signature {
            margin-top: 40px;
            width: 100%;
        }

        .signature-line {
            margin-top: 45px;
            border-top: 1px solid #111827;
            width: 280px;
            padding-top: 6px;
            font-size: 10px;
            text-align: center;
        }

        .muted {
            color: #6b7280;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">Relatório individual de fechamento</div>
        <div class="subtitle">{{ $snapshot->name }}</div>
        <div class="subtitle">
            Período: {{ $snapshot->period_start->format('d/m/Y') }} a {{ $snapshot->period_end->format('d/m/Y') }}
        </div>

        <div class="meta">
            <div><strong>Funcionário:</strong> {{ $employee->employee_name }}</div>
            <div><strong>Matrícula:</strong> {{ $employee->employee_registration ?: '—' }}</div>
            <div><strong>Status do fechamento:</strong> {{ $snapshot->status->label() }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 9%;">Data</th>
                <th style="width: 6%;">Dia</th>
                <th style="width: 20%;">Jornada esperada</th>
                <th style="width: 20%;">Registros</th>
                <th style="width: 9%;">Justificadas</th>
                <th style="width: 8%;">Atrasos</th>
                <th style="width: 8%;">Extras</th>
                <th style="width: 8%;">Faltas</th>
                <th style="width: 8%;">Suspensões</th>
                <th style="width: 8%;">Saldo</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($days as $day)
                <tr>
                    <td>{{ $day->work_date?->format('d/m/Y') }}</td>
                    <td class="center">{{ $day->weekday_label }}</td>
                    <td>
                        @php
                            $scheduleParts = [];

                            if ($day->expected_start_time && $day->expected_end_time) {
                                $scheduleParts[] =
                                    substr($day->expected_start_time, 0, 5) .
                                    ' - ' .
                                    substr($day->expected_end_time, 0, 5);
                            }

                            if ($day->expected_break_duration) {
                                $scheduleParts[] = 'Int. ' . substr($day->expected_break_duration, 0, 5);
                            }
                        @endphp

                        {{ count($scheduleParts) ? implode(' | ', $scheduleParts) : '—' }}
                    </td>
                    <td>{{ collect($day->records ?? [])->implode(' | ') ?: '—' }}</td>
                    <td class="right">{{ $day->justified_minutes }}</td>
                    <td class="right">{{ $day->late_minutes }}</td>
                    <td class="right">{{ $day->extra_minutes }}</td>
                    <td class="right">{{ $day->absence_minutes }}</td>
                    <td class="right">{{ $day->suspension_minutes }}</td>
                    <td class="right">{{ $day->balance_minutes }}</td>
                </tr>
            @endforeach

            <tr class="totals">
                <td colspan="4" class="right">Totais</td>
                <td class="right">{{ $employee->justified_minutes }}</td>
                <td class="right">{{ $employee->late_minutes }}</td>
                <td class="right">{{ $employee->extra_minutes }}</td>
                <td class="right">{{ $employee->absence_minutes }}</td>
                <td class="right">{{ $employee->suspension_minutes }}</td>
                <td class="right">{{ $employee->final_balance_minutes }}</td>
            </tr>
        </tbody>
    </table>

    <div class="signature">
        <div class="muted">
            Declaro estar ciente das informações consolidadas neste período.
        </div>

        <div class="signature-line">
            Assinatura do funcionário
        </div>
    </div>
</body>

</html>
