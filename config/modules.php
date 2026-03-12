<?php

return [

    'users' => [

        'name' => 'Usuários',
        'description' => 'Gestão de usuários do sistema',
        'is_core' => true,

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de usuários',
                'description' => 'Permite visualizar a lista de usuários do sistema',
            ],

            'view' => [
                'name' => 'Ver usuário',
                'description' => 'Permite visualizar os dados de um usuário',
            ],

            'create' => [
                'name' => 'Cadastrar usuário',
                'description' => 'Permite cadastrar novos usuários',
            ],

            'update' => [
                'name' => 'Atualizar usuário',
                'description' => 'Permite editar os dados de um usuário',
            ],

            'delete' => [
                'name' => 'Excluir usuário',
                'description' => 'Permite excluir usuários do sistema',
            ],

            'attachCompany' => [
                'name' => 'Vincular empresas ao usuário',
                'description' => 'Permite associar empresas a um usuário',
            ],

            'attachRole' => [
                'name' => 'Vincular funções ao usuário',
                'description' => 'Permite associar funções a um usuário',
            ],

        ],
    ],

    'roles' => [

        'name' => 'Funções',
        'description' => 'Gestão de funções de acesso',
        'is_core' => true,

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de funções',
                'description' => 'Permite visualizar todas as funções do tenant',
            ],

            'view' => [
                'name' => 'Ver função',
                'description' => 'Permite visualizar os detalhes de uma função',
            ],

            'create' => [
                'name' => 'Cadastrar função',
                'description' => 'Permite criar novas funções',
            ],

            'update' => [
                'name' => 'Atualizar função',
                'description' => 'Permite editar uma função',
            ],

            'delete' => [
                'name' => 'Excluir função',
                'description' => 'Permite remover uma função',
            ],

            'attachPermission' => [
                'name' => 'Vincular permissões à função',
                'description' => 'Permite associar permissões a uma função',
            ],

        ],
    ],

    'audit' => [

        'name' => 'Auditoria',
        'description' => 'Registro de ações do sistema',
        'is_core' => true,

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de auditorias',
                'description' => 'Permite visualizar os registros de auditoria do sistema',
            ],

            'view' => [
                'name' => 'Ver auditoria',
                'description' => 'Permite visualizar os detalhes de um registro de auditoria',
            ],
        ],
    ],

    'support' => [

        'name' => 'Suporte',
        'description' => 'Gestão de chamados de suporte',

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de chamados',
                'description' => 'Permite visualizar a lista de chamados de suporte',
            ],

            'view' => [
                'name' => 'Ver chamado',
                'description' => 'Permite visualizar os detalhes de um chamado',
            ],

            'create' => [
                'name' => 'Abrir chamado',
                'description' => 'Permite abrir novos chamados de suporte',
            ],

            'reply' => [
                'name' => 'Responder chamado',
                'description' => 'Permite enviar respostas em chamados de suporte',
            ],

            'changeStatus' => [
                'name' => 'Alterar status do chamado',
                'description' => 'Permite alterar o status de um chamado',
            ],

            'delete' => [
                'name' => 'Excluir chamado',
                'description' => 'Permite excluir chamados do sistema',
            ],

            'manageAll' => [
                'name' => 'Gerenciar todos os chamados',
                'description' => 'Permite visualizar e operar todos os chamados da empresa em contexto',
            ],

        ],
    ],

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
