<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório geral consolidado de fechamento</title>
    <style>
        @page {
            size: A4 landscape;
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

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
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

        .right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">Relatório geral consolidado de fechamento</div>
        <div class="subtitle">{{ $snapshot->name }}</div>
        <div class="subtitle">
            Período: {{ $snapshot->period_start->format('d/m/Y') }} a {{ $snapshot->period_end->format('d/m/Y') }}
        </div>

        <div class="meta">
            <div>Funcionários incluídos: {{ $employees->count() }}</div>
            <div>Consolidado em: {{ $snapshot->consolidated_at?->format('d/m/Y H:i') ?: '—' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 24%;">Funcionário</th>
                <th style="width: 12%;">Matrícula</th>
                <th style="width: 10%;">Justificadas</th>
                <th style="width: 9%;">Atrasos</th>
                <th style="width: 9%;">Extras</th>
                <th style="width: 9%;">Faltas</th>
                <th style="width: 10%;">Suspensões</th>
                <th style="width: 8%;">Ajuste</th>
                <th style="width: 9%;">Saldo final</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($employees as $employee)
                <tr>
                    <td>{{ $employee->employee_name }}</td>
                    <td>{{ $employee->employee_registration ?: '—' }}</td>
                    <td class="right">{{ $employee->justified_minutes }}</td>
                    <td class="right">{{ $employee->late_minutes }}</td>
                    <td class="right">{{ $employee->extra_minutes }}</td>
                    <td class="right">{{ $employee->absence_minutes }}</td>
                    <td class="right">{{ $employee->suspension_minutes }}</td>
                    <td class="right">{{ $employee->adjustment_minutes }}</td>
                    <td class="right">{{ $employee->final_balance_minutes }}</td>
                </tr>
            @endforeach

            <tr class="totals">
                <td colspan="2" class="right">Totais</td>
                <td class="right">{{ $employees->sum('justified_minutes') }}</td>
                <td class="right">{{ $employees->sum('late_minutes') }}</td>
                <td class="right">{{ $employees->sum('extra_minutes') }}</td>
                <td class="right">{{ $employees->sum('absence_minutes') }}</td>
                <td class="right">{{ $employees->sum('suspension_minutes') }}</td>
                <td class="right">{{ $employees->sum('adjustment_minutes') }}</td>
                <td class="right">{{ $employees->sum(fn($employee) => $employee->final_balance_minutes) }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
