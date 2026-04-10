<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de fornecedores</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de fornecedores',
        'subtitle' => 'Cadastro compartilhado de fornecedores do tenant',
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
                <th>Razão social</th>
                <th>Fantasia</th>
                <th>Documento</th>
                <th>E-mail</th>
                <th>Contato</th>
                <th>Cidade/UF</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier['id'] }}</td>
                    <td>{{ $supplier['name'] }}</td>
                    <td>{{ $supplier['trade_name'] ?: '—' }}</td>
                    <td>{{ $supplier['document'] }}</td>
                    <td>{{ $supplier['email'] ?: '—' }}</td>
                    <td>{{ $supplier['mobile'] ?: ($supplier['phone'] ?: '—') }}</td>
                    <td>
                        {{ $supplier['city'] ?: '—' }}
                        @if ($supplier['state'])
                            /{{ $supplier['state'] }}
                        @endif
                    </td>
                    <td>{{ $supplier['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhum fornecedor encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
