<?php
return [
    'main' => [
        'title'  => 'Главная',
        'icon'   => 'bi-house',
        'single' => true,
        'module' => 'main',
        'onload' => 1,
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
            ['slug' => 'nomenclature.manage.view',  'module' => 'nomenclature',   'title' => 'Номенклатура',      'icon' => 'bi-boxes'],
            ['slug' => 'products.manage.view',      'module' => 'products',       'title' => 'Товары',            'icon' => 'bi-cart3'],
            ['slug' => 'users.manage.view',         'module' => 'users',          'title' => 'Сотрудники',        'icon' => 'bi-people'],
            ['slug' => 'objects.manage.view',       'module' => 'objects',        'title' => 'Объекты',           'icon' => 'bi-shop'],
            ['slug' => 'organizations.manage.view', 'module' => 'organizations',  'title' => 'Организации',       'icon' => 'bi-building'],
            ['slug' => 'geography.manage.view',     'module' => 'geography',      'title' => 'География',         'icon' => 'bi-globe-americas'],
            ['slug' => 'units.manage.view',         'module' => 'units',          'title' => 'Единицы измерения', 'icon' => 'bi-rulers'],
        ]
    ],
];
