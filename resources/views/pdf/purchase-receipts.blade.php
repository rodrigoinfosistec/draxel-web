<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de recebimentos de compra</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de recebimentos de compra',
        'subtitle' => 'Listagem consolidada dos recebimentos de compra',
    ])

    @include('pdf.partials.report-filters', [
        'filters' => $filterSummary,
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Número</th>
                <th>NF</th>
                <th>Fornecedor</th>
                <th>Depósito</th>
                <th>Data</th>
                <th>Status</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($receipts as $receipt)
                <tr>
                    <td>{{ $receipt['id'] }}</td>
                    <td>{{ $receipt['number'] }}</td>
                    <td>{{ $receipt['invoice_number'] }}</td>
                    <td>{{ $receipt['supplier_name'] }}</td>
                    <td>{{ $receipt['warehouse_name'] }}</td>
                    <td>{{ $receipt['receipt_date'] }}</td>
                    <td>{{ $receipt['status_label'] }}</td>
                    <td>{{ $receipt['total_amount'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhum recebimento encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="report-footer">
        Gerado em {{ $generatedAt }}
    </div>
</body>

</html>
