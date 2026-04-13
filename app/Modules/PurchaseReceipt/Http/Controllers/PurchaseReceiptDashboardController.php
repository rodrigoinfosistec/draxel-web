<?php

namespace App\Modules\PurchaseReceipt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReceiptDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('purchaseReceipt.viewDashboard'), 403);

        $cards = [
            [
                'title' => 'Recebimentos de compra',
                'description' => 'Consulte os recebimentos cadastrados, filtre resultados e cadastre novas entradas de compra.',
                'href' => '/purchase-receipts/receipts',
                'permission' => 'purchaseReceipt.viewAnyPurchaseReceipt',
                'action_label' => 'Acessar recebimentos',
                'icon' => 'clipboard-list',
            ],
            [
                'title' => 'Pendências de lançamento',
                'description' => 'Visualize rapidamente os recebimentos em digitação e finalize as entradas pendentes.',
                'href' => '/purchase-receipts/receipts?status=draft',
                'permission' => 'purchaseReceipt.postPurchaseReceipt',
                'action_label' => 'Ver pendências',
                'icon' => 'truck',
            ],
        ];

        return Inertia::render('purchase-receipt/Dashboard', [
            'cards' => $cards,
        ]);
    }
}
