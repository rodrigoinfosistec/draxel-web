<?php

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Production\Enums\ProductionEntryStatus;
use App\Modules\Production\Models\ProductionEntry;
use App\Modules\Production\Models\ProductionEntryItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductionDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewDashboard', ProductionEntry::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        return Inertia::render('production/Dashboard', [
            'stats' => [
                'entries_count' => ProductionEntry::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->count(),

                'posted_count' => ProductionEntry::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->where('status', ProductionEntryStatus::Posted->value)
                    ->count(),

                'draft_count' => ProductionEntry::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->where('status', ProductionEntryStatus::Draft->value)
                    ->count(),

                'total_quantity' => (float) ProductionEntryItem::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->sum('quantity'),
            ],

            'cards' => [
                [
                    'title' => 'Entradas de produção',
                    'description' => 'Cadastre, acompanhe e lance entradas de produção no estoque.',
                    'href' => '/production/entries',
                    'permission' => 'production.viewAnyEntry',
                    'action_label' => 'Acessar',
                    'icon' => 'clipboard-list',
                ],
            ],
        ]);
    }
}
