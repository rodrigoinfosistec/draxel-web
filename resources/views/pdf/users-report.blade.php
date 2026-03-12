<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Usuários</title>
    @include('pdf.partials.report-styles')
</head>

<body>
    @include('pdf.partials.report-header', [
        'title' => 'Relatório de Usuários',
        'subtitle' => 'Usuários cadastrados no sistema',
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
                <th>Email</th>
                <th>Status</th>
                <th>Empresas</th>
                <th>Funções</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user['id'] }}</td>
                    <td>{{ $user['name'] }}</td>
                    <td>{{ $user['email'] }}</td>
                    <td>{{ $user['status'] }}</td>
                    <td>{{ $user['companies'] ?: '—' }}</td>
                    <td>{{ $user['roles'] ?: '—' }}</td>
                    <td>{{ $user['created_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nenhum usuário encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.report-footer')
</body>

</html>
