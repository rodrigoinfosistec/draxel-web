<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('order.viewDashboard'), 403);

        $tenantId = $request->user()->tenant_id;
        $companyId = session('current_company_id');

        $totalOrders = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->count();

        $draftOrders = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('status', 'draft')
            ->count();

        $confirmedOrders = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('status', 'confirmed')
            ->count();

        $cancelledOrders = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('status', 'cancelled')
            ->count();

        return Inertia::render('order/Dashboard', [
            'cards' => [
                [
                    'title' => 'Pedidos',
                    'description' => 'Cadastre, acompanhe e confirme saídas operacionais com segurança.',
                    'href' => '/order/orders',
                    'permission' => 'order.viewAnyOrder',
                    'action_label' => 'Acessar pedidos',
                    'icon' => 'shopping-cart',
                ],
            ],
            'summary' => [
                'total_orders' => $totalOrders,
                'draft_orders' => $draftOrders,
                'confirmed_orders' => $confirmedOrders,
                'cancelled_orders' => $cancelledOrders,
            ],
        ]);
    }
}
