<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Unidades de Medida</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Unidades de Medida',
        'subtitle' => 'Unidades compartilhadas do tenant',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Todas as empresas',
    ])

    @include('pdf.partials.report-filters', [
        'items' => [
            'Busca' => $filters['search'] ?? null,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Sigla</th>
                <th>Descrição</th>
                <th>Status</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($unitOfMeasures as $unitOfMeasure)
                <tr>
                    <td>{{ $unitOfMeasure['id'] }}</td>
                    <td>{{ $unitOfMeasure['name'] }}</td>
                    <td>{{ $unitOfMeasure['symbol'] }}</td>
                    <td>{{ $unitOfMeasure['description'] ?: '—' }}</td>
                    <td>{{ $unitOfMeasure['status'] }}</td>
                    <td>{{ $unitOfMeasure['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nenhuma unidade encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
