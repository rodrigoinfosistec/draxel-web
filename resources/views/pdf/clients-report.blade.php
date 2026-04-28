<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de clientes</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de clientes',
        'subtitle' => 'Listagem de clientes cadastrados',
    ])

    @include('pdf.partials.report-filters', [
        'filters' => [
            'Busca' => $filters['search'] ?: 'Todos',
            'Gerado em' => $generatedAt,
        ],
    ])

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Documento</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Endereço</th>
                <th>Criado em</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td>{{ $client['id'] }}</td>
                    <td>{{ $client['name'] }}</td>
                    <td>{{ $client['document'] ?: '—' }}</td>
                    <td>{{ $client['email'] ?: '—' }}</td>
                    <td>{{ $client['phone'] ?: '—' }}</td>
                    <td>{{ $client['address'] ?: '—' }}</td>
                    <td>{{ $client['created_at'] ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-cell">
                        Nenhum cliente encontrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
