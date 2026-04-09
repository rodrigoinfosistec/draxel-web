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
                'description' => 'Permite visualizar todas as funções do grupo',
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

            'viewAnyClockRecordImport' => [
                'name' => 'Ver lista de importações de ponto',
                'description' => 'Permite visualizar a lista de importações de registros de ponto',
            ],

            'viewClockRecordImport' => [
                'name' => 'Ver importação de ponto',
                'description' => 'Permite visualizar os detalhes de uma importação de registros de ponto',
            ],

            'createClockRecordImport' => [
                'name' => 'Criar importação de ponto',
                'description' => 'Permite enviar arquivos para importação de registros de ponto',
            ],

            'updateClockRecordImport' => [
                'name' => 'Atualizar importação de ponto',
                'description' => 'Permite revisar, ajustar e desconsiderar itens de uma importação de registros de ponto',
            ],

            'reverseClockRecordImport' => [
                'name' => 'Reverter importação de ponto',
                'description' => 'Permite reverter uma importação já lançada, desde que seus registros ainda não tenham sido utilizados em fechamento',
            ],

            'deleteClockRecordImport' => [
                'name' => 'Excluir importação de ponto',
                'description' => 'Permite excluir importações de registros de ponto que ainda não foram lançadas',
            ],

            'launchClockRecordImport' => [
                'name' => 'Lançar importação de ponto',
                'description' => 'Permite lançar os registros validados da importação para os registros de ponto',
            ],

            'exportClockRecordImport' => [
                'name' => 'Exportar importações de ponto',
                'description' => 'Permite exportar relatórios e listagens de importações de ponto',
            ],

            'viewAnyApuration' => [
                'name' => 'Ver apurações de ponto',
                'description' => 'Permite visualizar a apuração de ponto por período e funcionário',
            ],

            'exportApuration' => [
                'name' => 'Exportar apurações de ponto',
                'description' => 'Permite exportar relatórios e listagens da apuração de ponto',
            ],

            'viewAnyBankHour' => [
                'name' => 'Ver banco de horas',
                'description' => 'Permite visualizar saldos e movimentos do banco de horas',
            ],

            'viewBankHour' => [
                'name' => 'Ver detalhe do banco de horas',
                'description' => 'Permite visualizar os detalhes do banco de horas por funcionário',
            ],

            'createBankHourEntry' => [
                'name' => 'Cadastrar movimento de banco de horas',
                'description' => 'Permite registrar créditos e débitos no banco de horas',
            ],

            'updateBankHourEntry' => [
                'name' => 'Atualizar movimento de banco de horas',
                'description' => 'Permite editar lançamentos manuais do banco de horas',
            ],

            'exportBankHour' => [
                'name' => 'Exportar banco de horas',
                'description' => 'Permite exportar relatórios e listagens do banco de horas',
            ],

            'viewAnyHourBankSnapshot' => [
                'name' => 'Ver lista de fechamentos de banco de horas',
                'description' => 'Permite visualizar a lista de fechamentos de banco de horas',
            ],

            'viewHourBankSnapshot' => [
                'name' => 'Ver fechamento de banco de horas',
                'description' => 'Permite visualizar os detalhes de um fechamento de banco de horas',
            ],

            'createHourBankSnapshot' => [
                'name' => 'Cadastrar fechamento de banco de horas',
                'description' => 'Permite criar novos fechamentos de banco de horas',
            ],

            'updateHourBankSnapshot' => [
                'name' => 'Atualizar fechamento de banco de horas',
                'description' => 'Permite incluir funcionários, recapturar dados e ajustar fechamentos de banco de horas',
            ],

            'consolidateHourBankSnapshot' => [
                'name' => 'Consolidar fechamento de banco de horas',
                'description' => 'Permite consolidar fechamentos de banco de horas e gerar efeito histórico operacional',
            ],

            'reverseHourBankSnapshot' => [
                'name' => 'Reverter fechamento de banco de horas',
                'description' => 'Permite reverter fechamentos consolidados de banco de horas mediante controle administrativo',
            ],

            'exportHourBankSnapshot' => [
                'name' => 'Exportar fechamento de banco de horas',
                'description' => 'Permite exportar relatórios individuais e gerais dos fechamentos de banco de horas',
            ],

            'deleteHourBankSnapshot' => [
                'name' => 'Excluir fechamento de banco de horas',
                'description' => 'Permite excluir fechamentos de banco de horas que ainda não foram consolidados',
            ],
        ],
    ],

    'inventory' => [

        'name' => 'Estoque',
        'description' => 'Controle de depósitos, movimentações e posição de estoque',

        'permissions' => [

            'viewDashboard' => [
                'name' => 'Ver dashboard de estoque',
                'description' => 'Permite visualizar a dashboard do módulo de estoque',
            ],

            'viewAnyWarehouse' => [
                'name' => 'Ver lista de depósitos',
                'description' => 'Permite visualizar a lista de depósitos da empresa em contexto',
            ],

            'viewWarehouse' => [
                'name' => 'Ver depósito',
                'description' => 'Permite visualizar os dados de um depósito',
            ],

            'createWarehouse' => [
                'name' => 'Cadastrar depósito',
                'description' => 'Permite cadastrar novos depósitos',
            ],

            'updateWarehouse' => [
                'name' => 'Atualizar depósito',
                'description' => 'Permite editar depósitos existentes',
            ],

            'deleteWarehouse' => [
                'name' => 'Excluir depósito',
                'description' => 'Permite excluir depósitos sem movimentações e sem saldo',
            ],

            'exportWarehouse' => [
                'name' => 'Exportar depósitos',
                'description' => 'Permite exportar relatórios e listagens de depósitos',
            ],

            'viewAnyStockMovement' => [
                'name' => 'Ver lista de movimentações de estoque',
                'description' => 'Permite visualizar a lista de movimentações de estoque',
            ],

            'viewStockMovement' => [
                'name' => 'Ver movimentação de estoque',
                'description' => 'Permite visualizar os dados de uma movimentação de estoque',
            ],

            'createStockMovement' => [
                'name' => 'Cadastrar movimentação de estoque',
                'description' => 'Permite registrar entradas, saídas e ajustes de estoque',
            ],

            'exportStockMovement' => [
                'name' => 'Exportar movimentações de estoque',
                'description' => 'Permite exportar relatórios e listagens de movimentações de estoque',
            ],

            'viewAnyProductStock' => [
                'name' => 'Ver posição de estoque por depósito',
                'description' => 'Permite visualizar a posição de estoque por depósito',
            ],

            'viewProductStock' => [
                'name' => 'Ver detalhe de posição por depósito',
                'description' => 'Permite visualizar os dados de uma posição de estoque por depósito',
            ],

            'exportProductStock' => [
                'name' => 'Exportar posição por depósito',
                'description' => 'Permite exportar relatórios e listagens da posição de estoque por depósito',
            ],

            'viewInventoryPosition' => [
                'name' => 'Ver posição consolidada de estoque',
                'description' => 'Permite visualizar a posição consolidada de estoque da empresa',
            ],

            'exportInventoryPosition' => [
                'name' => 'Exportar posição consolidada de estoque',
                'description' => 'Permite exportar relatórios e listagens da posição consolidada de estoque',
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

    'departments' => [

        'name' => 'Departamentos',
        'description' => 'Gestão de departamentos',
        'is_core' => true,

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de departamentos',
                'description' => 'Permite visualizar a lista de departamentos do grupo',
            ],

            'view' => [
                'name' => 'Ver departamento',
                'description' => 'Permite visualizar os dados de um departamento',
            ],

            'create' => [
                'name' => 'Cadastrar departamento',
                'description' => 'Permite cadastrar novos departamentos',
            ],

            'update' => [
                'name' => 'Atualizar departamento',
                'description' => 'Permite editar departamentos',
            ],

            'delete' => [
                'name' => 'Excluir departamento',
                'description' => 'Permite excluir departamentos',
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
                'description' => 'Permite visualizar a lista de cargos do grupo',
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
                'description' => 'Permite visualizar a lista de feriados do grupo',
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

    'contacts' => [

        'name' => 'Agenda',
        'description' => 'Gestão de contatos do grupo',
        'is_core' => true,

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de contatos',
                'description' => 'Permite visualizar a lista de contatos do grupo',
            ],

            'view' => [
                'name' => 'Ver contato',
                'description' => 'Permite visualizar os dados de um contato',
            ],

            'create' => [
                'name' => 'Cadastrar contato',
                'description' => 'Permite cadastrar novos contatos',
            ],

            'update' => [
                'name' => 'Atualizar contato',
                'description' => 'Permite editar contatos',
            ],

            'delete' => [
                'name' => 'Excluir contato',
                'description' => 'Permite excluir contatos',
            ],
        ],
    ],

    'productCategories' => [

        'name' => 'Categorias de produto',
        'description' => 'Gestão de categorias compartilhadas de produto',
        'is_core' => true,

        'permissions' => [

            'viewAny' => [
                'name' => 'Ver lista de categorias de produto',
                'description' => 'Permite visualizar a lista de categorias de produto do grupo',
            ],

            'view' => [
                'name' => 'Ver categoria de produto',
                'description' => 'Permite visualizar os dados de uma categoria de produto',
            ],

            'create' => [
                'name' => 'Cadastrar categoria de produto',
                'description' => 'Permite cadastrar novas categorias de produto',
            ],

            'update' => [
                'name' => 'Atualizar categoria de produto',
                'description' => 'Permite editar categorias de produto',
            ],

            'delete' => [
                'name' => 'Excluir categoria de produto',
                'description' => 'Permite excluir categorias de produto',
            ],

        ],
    ],

    'brands' => [
        'name' => 'Marcas',
        'description' => 'Gestão de marcas compartilhadas de produto',
        'is_core' => true,

        'permissions' => [
            'viewAny' => [
                'name' => 'Ver lista de marcas',
                'description' => 'Permite visualizar a lista de marcas do grupo',
            ],

            'view' => [
                'name' => 'Ver marca',
                'description' => 'Permite visualizar os dados de uma marca',
            ],

            'create' => [
                'name' => 'Cadastrar marca',
                'description' => 'Permite cadastrar novas marcas',
            ],

            'update' => [
                'name' => 'Atualizar marca',
                'description' => 'Permite editar marcas',
            ],

            'delete' => [
                'name' => 'Excluir marca',
                'description' => 'Permite excluir marcas',
            ],
        ],
    ],

    'unitOfMeasures' => [
        'name' => 'Unidades de medida',
        'description' => 'Gestão de unidades de medida compartilhadas de produto',
        'is_core' => true,

        'permissions' => [
            'viewAny' => [
                'name' => 'Ver lista de unidades de medida',
                'description' => 'Permite visualizar a lista de unidades de medida do grupo',
            ],

            'view' => [
                'name' => 'Ver unidade de medida',
                'description' => 'Permite visualizar os dados de uma unidade de medida',
            ],

            'create' => [
                'name' => 'Cadastrar unidade de medida',
                'description' => 'Permite cadastrar novas unidades de medida',
            ],

            'update' => [
                'name' => 'Atualizar unidade de medida',
                'description' => 'Permite editar unidades de medida',
            ],

            'delete' => [
                'name' => 'Excluir unidade de medida',
                'description' => 'Permite excluir unidades de medida',
            ],
        ],
    ],

    'products' => [
        'name' => 'Produtos',
        'description' => 'Gestão de produtos compartilhados do tenant',
        'is_core' => true,

        'permissions' => [
            'dashboard' => [
                'name' => 'Acessar dashboard de produtos',
                'description' => 'Permite acessar o dashboard principal da área de produtos',
            ],

            'viewAny' => [
                'name' => 'Ver lista de produtos',
                'description' => 'Permite visualizar a lista de produtos do tenant',
            ],

            'view' => [
                'name' => 'Ver produto',
                'description' => 'Permite visualizar os dados de um produto',
            ],

            'create' => [
                'name' => 'Cadastrar produto',
                'description' => 'Permite cadastrar novos produtos',
            ],

            'update' => [
                'name' => 'Atualizar produto',
                'description' => 'Permite editar produtos',
            ],

            'delete' => [
                'name' => 'Excluir produto',
                'description' => 'Permite excluir produtos',
            ],
        ],
    ],

    'production' => [

        'name' => 'Produção',
        'description' => 'Gestão de entradas de produção e seus reflexos no estoque',

        'permissions' => [

            'viewDashboard' => [
                'name' => 'Ver dashboard de produção',
                'description' => 'Permite visualizar o dashboard do módulo de produção',
            ],

            'viewAnyEntry' => [
                'name' => 'Ver lista de entradas de produção',
                'description' => 'Permite visualizar a lista de entradas de produção',
            ],

            'viewEntry' => [
                'name' => 'Ver entrada de produção',
                'description' => 'Permite visualizar os dados de uma entrada de produção',
            ],

            'createEntry' => [
                'name' => 'Cadastrar entrada de produção',
                'description' => 'Permite cadastrar novas entradas de produção',
            ],

            'updateEntry' => [
                'name' => 'Atualizar entrada de produção',
                'description' => 'Permite editar entradas de produção em rascunho',
            ],

            'deleteEntry' => [
                'name' => 'Excluir entrada de produção',
                'description' => 'Permite excluir entradas de produção em rascunho',
            ],

            'postEntry' => [
                'name' => 'Lançar entrada de produção',
                'description' => 'Permite lançar entradas de produção no estoque',
            ],

            'cancelEntry' => [
                'name' => 'Cancelar entrada de produção',
                'description' => 'Permite cancelar entradas de produção já lançadas',
            ],

            'exportEntry' => [
                'name' => 'Exportar entradas de produção',
                'description' => 'Permite exportar relatórios das entradas de produção',
            ],

        ],
    ],


];
