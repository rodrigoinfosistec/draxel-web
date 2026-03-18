<?php

namespace App\Modules\Worktime\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorktimeDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('worktime.viewAny'), 403);

        return Inertia::render('worktime/Dashboard', [
            'cards' => [
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
            ],
        ]);
    }
}
