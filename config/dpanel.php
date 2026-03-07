<?php

$baseHost = config('app.base_domain');

return [

    'tenant' => [
        'slug' => 'dpanel',
        'name' => 'Painel Global',
    ],

    'company' => [
        'name' => 'Empresa Global',
        'alias' => 'Empresa Global',
        'cnpj' => '00000000000000',
        'color' => '#7C3AED',
    ],

    'role' => [
        'slug' => 'admin',
        'name' => 'Administrador',
        'description' => 'Acesso completo ao sistema',
    ],

    'user' => [
        [
            'email' => "super@dpanel.{$baseHost}",
            'name' => "Super Usuário",
            'password' => 'App1000#',
            'email_verified_at' => now(),
            'is_admin' => true,
        ]
    ],

];
