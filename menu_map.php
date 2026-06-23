<?php
return [
    'dashboard' => [
        'title'  => 'Главная',
        'icon'   => 'bi-house',
        'single' => true,
    ],
    'orders' => [
        'title'  => 'Заказы',
        'icon'   => 'bi-bag',
        'slug'   => 'orders',
        'single' => true,
    ],
    'finance' => [
        'title' => 'Финансы',
        'icon'  => 'bi-cash-stack',
        'items' => [
            ['slug' => 'geography.payments', 'title' => 'Платежи',  'icon' => 'bi-credit-card'],
            ['slug' => 'geography.reports',  'title' => 'Отчёты',   'icon' => 'bi-bar-chart'],
        ]
    ],
    'warehouse' => [
        'title' => 'Склад',
        'icon'  => 'bi-box-seam',
        'items' => [
            ['slug' => 'geography.documents', 'title' => 'Документы', 'icon' => 'bi-file-earmark-text'],
            ['slug' => 'geography',           'title' => 'Остатки',   'icon' => 'bi-archive'],
        ]
    ],
    'reference' => [
        'title' => 'Справочники',
        'icon'  => 'bi-journal-bookmark',
        'items' => [
            ['slug' => 'users',     'title' => 'Сотрудники', 'icon' => 'bi-people'],
            ['slug' => 'geography', 'title' => 'География',  'icon' => 'bi-globe-americas'],
        ]
    ],
];
