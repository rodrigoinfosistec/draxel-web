<?php

namespace App\Modules\Worktime\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorktimeDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('worktime.viewAny'), 403);

        $company = CompanyContext::current();

        $cards = [
            [
                'title' => 'Eventos de funcionários',
                'description' => 'Gerencie férias, folgas, suspensões, declarações e abonos.',
                'href' => '/worktime/employee-events',
                'permission' => 'worktime.viewAny',
                'action_label' => 'Acessar eventos',
                'icon' => 'calendar-clock',
            ],
            [
                'title' => 'Registros de ponto',
                'description' => 'Cadastre, ajuste e trate os registros manuais de ponto.',
                'href' => '/worktime/clock-records',
                'permission' => 'worktime.viewAnyClockRecord',
                'action_label' => 'Acessar registros',
                'icon' => 'clock-3',
            ],
            [
                'title' => 'Importações',
                'description' => 'Envie arquivos TXT, revise divergências e lance os registros.',
                'href' => '/worktime/clock-record-imports',
                'permission' => 'worktime.viewAnyClockRecordImport',
                'action_label' => 'Acessar importações',
                'icon' => 'file-clock',
            ],
            [
                'title' => 'Apuração',
                'description' => 'Apure o período por funcionário e dia antes do fechamento.',
                'href' => '/worktime/apurations',
                'permission' => 'worktime.viewAnyApuration',
                'action_label' => 'Acessar apuração',
                'icon' => 'calculator',
            ],
        ];

        if ($company?->uses_hour_bank) {
            $cards[] = [
                'title' => 'Banco de horas',
                'description' => 'Consulte saldos e movimentos de créditos e débitos por funcionário.',
                'href' => '/worktime/bank-hours',
                'permission' => 'worktime.viewAnyBankHour',
                'action_label' => 'Acessar banco de horas',
                'icon' => 'wallet-cards',
            ];
        }

        return Inertia::render('worktime/Dashboard', [
            'cards' => $cards,
        ]);
    }
}
