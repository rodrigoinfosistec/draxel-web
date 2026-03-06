<?php

return [

    'worktime' => [

        'name' => 'Ponto',
        'description' => 'Controle de jornada e registro de ponto',

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de registros de ponto',
                'description' => 'Permite visualizar registros de jornada',
            ],

            'create' => [
                'name' => 'Registrar ponto',
                'description' => 'Permite registrar novos pontos',
            ],

            'update' => [
                'name' => 'Editar registro de ponto',
                'description' => 'Permite editar registros de jornada',
            ],

        ],
    ],

    'inventory' => [

        'name' => 'Estoque',
        'description' => 'Controle de estoque',

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver estoque',
                'description' => 'Permite visualizar o estoque',
            ],

            'create' => [
                'name' => 'Cadastrar item',
                'description' => 'Permite cadastrar itens no estoque',
            ],

            'update' => [
                'name' => 'Editar item',
                'description' => 'Permite editar itens do estoque',
            ],

        ],
    ],

    'order' => [

        'name' => 'Pedido',
        'description' => 'Gestão de pedidos',

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver pedidos',
                'description' => 'Permite visualizar pedidos',
            ],

            'create' => [
                'name' => 'Criar pedido',
                'description' => 'Permite registrar novos pedidos',
            ],

            'update' => [
                'name' => 'Editar pedido',
                'description' => 'Permite editar pedidos',
            ],

        ],
    ],

];
