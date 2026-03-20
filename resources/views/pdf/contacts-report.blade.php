<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Contatos</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Contatos',
        'subtitle' => 'Contatos cadastrados no tenant',
        'generatedAt' => $generatedAt,
        'tenantName' => $tenantName ?? 'Tenant',
        'companyName' => $companyName ?? 'Empresa',
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
                <th>Descrição</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Rótulo</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contacts as $contact)
                @foreach ($contact['items'] as $index => $item)
                    <tr>
                        @if ($index === 0)
                            <td>{{ $contact['id'] }}</td>
                            <td>{{ $contact['name'] }}</td>
                            <td>{{ $contact['description'] ?: '—' }}</td>
                            <td>{{ $item['type'] ?: '—' }}</td>
                            <td>{{ $item['value'] }}</td>
                            <td>{{ $item['label'] ?: '—' }}</td>
                            <td>{{ $contact['created_at'] ?? '—' }}</td>
                        @else
                            <td>{{ $contact['id'] }}</td>
                            <td>{{ $contact['name'] }}</td>
                            <td>{{ $contact['description'] ?: '—' }}</td>
                            <td>{{ $item['type'] ?: '—' }}</td>
                            <td>{{ $item['value'] }}</td>
                            <td>{{ $item['label'] ?: '—' }}</td>
                            <td>{{ $contact['created_at'] ?? '—' }}</td>
                        @endif
                    </tr>
                @endforeach

                @if (count($contact['items']) === 0)
                    <tr>
                        <td>{{ $contact['id'] }}</td>
                        <td>{{ $contact['name'] }}</td>
                        <td>{{ $contact['description'] ?: '—' }}</td>
                        <td>—</td>
                        <td>—</td>
                        <td>—</td>
                        <td>{{ $contact['created_at'] ?? '—' }}</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="7">Nenhum contato encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
