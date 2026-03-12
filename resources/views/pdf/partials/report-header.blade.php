<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="report-title">{{ $title }}</div>
                <div class="report-subtitle">{{ $subtitle }}</div>
                <div class="report-subtitle">Gerado em {{ $generatedAt }}</div>
            </td>
            <td class="header-right">
                <div class="tenant-name">{{ $tenantName ?? 'Tenant' }}</div>
                <div class="company-name">{{ $companyName ?? 'Empresa' }}</div>
            </td>
        </tr>
    </table>
</div>
