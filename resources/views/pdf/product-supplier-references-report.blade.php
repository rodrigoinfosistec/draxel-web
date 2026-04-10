<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de vínculos fornecedor x produto</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de vínculos fornecedor x produto',
        'subtitle' => 'Base de vínculo inteligente para recebimento por XML',
    ])

    @include('pdf.partials.report-filters', [
        'filters' => [
            'Busca' => $filters['search'] ?: '—',
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fornecedor</th>
                <th>Produto</th>
                <th>SKU</th>
                <th>Cód. fornecedor</th>
                <th>Descrição fornecedor</th>
                <th>GTIN/EAN</th>
                <th>Unidade</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($references as $reference)
                <tr>
                    <td>{{ $reference['id'] }}</td>
                    <td>{{ $reference['supplier'] ?: '—' }}</td>
                    <td>{{ $reference['product'] ?: '—' }}</td>
                    <td>{{ $reference['product_sku'] ?: '—' }}</td>
                    <td>{{ $reference['supplier_product_code'] ?: '—' }}</td>
                    <td>{{ $reference['supplier_product_description'] ?: '—' }}</td>
                    <td>{{ $reference['barcode'] ?: '—' }}</td>
                    <td>{{ $reference['unit'] ?: '—' }}</td>
                    <td>{{ $reference['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Nenhum vínculo encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
