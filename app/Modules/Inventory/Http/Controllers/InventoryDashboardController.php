<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\ProductStock;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewDashboard', Warehouse::class);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        return Inertia::render('inventory/Dashboard', [
            'stats' => [
                'warehouses_count' => Warehouse::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->count(),

                'products_with_stock' => ProductStock::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->where('quantity', '>', 0)
                    ->distinct('product_id')
                    ->count('product_id'),

                'total_quantity' => (float) ProductStock::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->sum('quantity'),

                'movements_count' => StockMovement::query()
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->count(),
            ],

            'cards' => [
                [
                    'title' => 'Posição consolidada',
                    'description' => 'Saldo total dos produtos na empresa.',
                    'href' => '/inventory/positions/consolidated',
                    'permission' => 'inventory.viewInventoryPosition',
                    'action_label' => 'Consultar',
                    'icon' => 'package-search',
                ],
                [
                    'title' => 'Movimentações',
                    'description' => 'Entradas, saídas e ajustes por depósito.',
                    'href' => '/inventory/movements',
                    'permission' => 'inventory.viewAnyStockMovement',
                    'action_label' => 'Acessar',
                    'icon' => 'clipboard-list',
                ],
                [
                    'title' => 'Posição por depósito',
                    'description' => 'Saldo atual dos produtos em cada depósito.',
                    'href' => '/inventory/positions',
                    'permission' => 'inventory.viewAnyProductStock',
                    'action_label' => 'Consultar',
                    'icon' => 'boxes',
                ],
                [
                    'title' => 'Depósitos',
                    'description' => 'Cadastre e organize os depósitos da empresa.',
                    'href' => '/inventory/warehouses',
                    'permission' => 'inventory.viewAnyWarehouse',
                    'action_label' => 'Acessar',
                    'icon' => 'building-2',
                ],
            ],
        ]);
    }
}
