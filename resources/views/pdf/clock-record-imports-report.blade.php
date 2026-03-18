<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Importações de Ponto</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Importações de Ponto',
        'subtitle' => 'Importações cadastradas no módulo de ponto',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Arquivo</th>
                <th>Device</th>
                <th>Status</th>
                <th>Total</th>
                <th>Válidos</th>
                <th>Divergentes</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($imports as $import)
                <tr>
                    <td>{{ $import['id'] }}</td>
                    <td>{{ $import['original_filename'] }}</td>
                    <td>{{ $import['device_name'] ?: '—' }}</td>
                    <td>{{ $import['status'] ?: '—' }}</td>
                    <td>{{ $import['total_items'] }}</td>
                    <td>{{ $import['valid_items'] }}</td>
                    <td>{{ $import['invalid_items'] }}</td>
                    <td>{{ $import['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhuma importação encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
