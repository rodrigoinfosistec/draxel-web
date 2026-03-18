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

        'name' => 'Funções de usuário',
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
        'is_core' => true,

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

    'parameters' => [

        'name' => 'Parâmetros',
        'description' => 'Parâmetros operacionais da empresa em contexto',
        'is_core' => true,

        'permissions' => [

            'view' => [
                'name' => 'Ver parâmetros',
                'description' => 'Permite visualizar os parâmetros da empresa em contexto',
            ],

            'update' => [
                'name' => 'Atualizar parâmetros',
                'description' => 'Permite atualizar os parâmetros da empresa em contexto',
            ],

        ],
    ],

    'worktime' => [

        'name' => 'Ponto',
        'description' => 'Controle de jornada, ponto e eventos de funcionários',

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de eventos de ponto',
                'description' => 'Permite visualizar a lista de eventos do módulo de ponto',
            ],

            'view' => [
                'name' => 'Ver evento de ponto',
                'description' => 'Permite visualizar os dados de um evento do módulo de ponto',
            ],

            'create' => [
                'name' => 'Cadastrar evento de ponto',
                'description' => 'Permite cadastrar novos eventos de jornada para funcionários',
            ],

            'update' => [
                'name' => 'Atualizar evento de ponto',
                'description' => 'Permite editar eventos de jornada de funcionários',
            ],

            'delete' => [
                'name' => 'Excluir evento de ponto',
                'description' => 'Permite excluir eventos de jornada de funcionários',
            ],

            'export' => [
                'name' => 'Exportar eventos de ponto',
                'description' => 'Permite exportar relatórios e listagens do módulo de ponto',
            ],

            'viewAnyClockRecord' => [
                'name' => 'Ver lista de registros de ponto',
                'description' => 'Permite visualizar a lista de registros de ponto do módulo',
            ],

            'viewClockRecord' => [
                'name' => 'Ver registro de ponto',
                'description' => 'Permite visualizar os dados de um registro de ponto',
            ],

            'createClockRecord' => [
                'name' => 'Cadastrar registro de ponto',
                'description' => 'Permite cadastrar registros manuais de ponto',
            ],

            'updateClockRecord' => [
                'name' => 'Atualizar registro de ponto',
                'description' => 'Permite editar registros de ponto',
            ],

            'deleteClockRecord' => [
                'name' => 'Excluir registro de ponto',
                'description' => 'Permite excluir registros de ponto',
            ],

            'exportClockRecord' => [
                'name' => 'Exportar registros de ponto',
                'description' => 'Permite exportar relatórios e listagens de registros de ponto',
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

    'positions' => [

        'name' => 'Cargos de funcionário',
        'description' => 'Gestão de cargos',
        'is_core' => true,

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de cargos',
                'description' => 'Permite visualizar a lista de cargos do tenant',
            ],

            'view' => [
                'name' => 'Ver cargo',
                'description' => 'Permite visualizar os dados de um cargo',
            ],

            'create' => [
                'name' => 'Cadastrar cargo',
                'description' => 'Permite cadastrar novos cargos',
            ],

            'update' => [
                'name' => 'Atualizar cargo',
                'description' => 'Permite editar cargos',
            ],

            'delete' => [
                'name' => 'Excluir cargo',
                'description' => 'Permite excluir cargos',
            ],

        ],
    ],

    'employees' => [

        'name' => 'Funcionários',
        'description' => 'Gestão de funcionários',
        'is_core' => true,

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de funcionários',
                'description' => 'Permite visualizar a lista de funcionários',
            ],

            'view' => [
                'name' => 'Ver funcionário',
                'description' => 'Permite visualizar os dados de um funcionário',
            ],

            'create' => [
                'name' => 'Cadastrar funcionário',
                'description' => 'Permite cadastrar novos funcionários',
            ],

            'update' => [
                'name' => 'Atualizar funcionário',
                'description' => 'Permite editar funcionários',
            ],

            'delete' => [
                'name' => 'Excluir funcionário',
                'description' => 'Permite excluir funcionários',
            ],

            'times' => [
                'name' => 'Gerenciar horário de funcionários',
                'description' => 'Permite gerenciar os horários padrão dos funcionários',
            ],
        ],
    ],

    'holidays' => [

        'name' => 'Feriados',
        'description' => 'Gestão de feriados',
        'is_core' => true,

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de feriados',
                'description' => 'Permite visualizar a lista de feriados do tenant',
            ],

            'view' => [
                'name' => 'Ver feriado',
                'description' => 'Permite visualizar os dados de um feriado',
            ],

            'create' => [
                'name' => 'Cadastrar feriado',
                'description' => 'Permite cadastrar novos feriados',
            ],

            'update' => [
                'name' => 'Atualizar feriado',
                'description' => 'Permite editar feriados',
            ],

            'delete' => [
                'name' => 'Excluir feriado',
                'description' => 'Permite excluir feriados',
            ],

        ],
    ],

];
