<?php

return [

    'users' => [

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

    'funções' => [

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

    'audit' => [

        'viewAny' => [
            'name' => 'Ver lista de auditorias',
            'description' => 'Permite visualizar os registros de auditoria do sistema',
        ],

        'view' => [
            'name' => 'Ver auditoria',
            'description' => 'Permite visualizar os detalhes de um registro de auditoria',
        ],
    ],

];
