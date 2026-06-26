<?php
return [
    'dashboard' => [
        'title'  => 'Главная',
        'icon'   => 'bi-house',
        'single' => true,
        'module' => 'main',
    ],
    'orders' => [
        'title'  => 'Заказы',
        'icon'   => 'bi-bag',
        'slug'   => 'orders',
        'single' => true,
        'module' => 'orders',
    ],
    'finance' => [
        'title' => 'Финансы',
        'icon'  => 'bi-cash-stack',
        'items' => [
            ['slug' => 'finance.payments',  'module' => 'payments',         'title' => 'Платежи',  'icon' => 'bi-credit-card'],
            ['slug' => 'finance.reports',   'module' => 'finance_reports',  'title' => 'Отчёты',   'icon' => 'bi-bar-chart'],
        ]
    ],
    'warehouse' => [
        'title' => 'Склад',
        'icon'  => 'bi-box-seam',
        'items' => [
            ['slug' => 'warehouse.documents', 'module' => 'documents', 'title' => 'Документы', 'icon' => 'bi-file-earmark-text'],
            ['slug' => 'warehouse.totals',    'module' => 'totals',    'title' => 'Остатки',   'icon' => 'bi-archive'],
        ]
    ],
    'reference' => [
        'title' => 'Справочники',
        'icon'  => 'bi-journal-bookmark',
        'items' => [
            ['slug' => 'users',                     'module' => 'users',          'title' => 'Сотрудники',    'icon' => 'bi-people'],
            ['slug' => 'geography',                 'module' => 'geography',      'title' => 'География',     'icon' => 'bi-globe-americas'],
            ['slug' => 'organizations.manage.view', 'module' => 'organizations',  'title' => 'Организации',   'icon' => 'bi-building'],
        ]
    ],
];
