<?php
return [
    'main' => [
        'title'  => 'Главная',
        'icon'   => 'bi-house',
        'single' => true,
        'module' => 'main',
        'onload' => 1,
    ],
    'turnover' => [
        'title' => 'Товарооборот',
        'icon'  => 'bi-arrow-left-right',
        'items' => [
            ['slug_prefix' => 'inventory.documents.', 'module' => 'inventory',   'title' => 'Документы',     'icon' => 'bi-file-earmark-text'],
            ['slug' => 'settlements.manage.view',     'module' => 'settlements', 'title' => 'Взаиморасчёты', 'icon' => 'bi-cash-stack'],
        ]
    ],
    'reference' => [
        'title' => 'Справочники',
        'icon'  => 'bi-journal-bookmark',
        'items' => [
            ['slug' => 'products.manage.view',      'module' => 'products',       'title' => 'Товары',            'icon' => 'bi-cart3'],
            ['slug' => 'nomenclature.manage.view',  'module' => 'nomenclature',   'title' => 'Номенклатура',      'icon' => 'bi-boxes'],
            ['slug' => 'users.manage.view',         'module' => 'users',          'title' => 'Сотрудники',        'icon' => 'bi-people'],
            ['slug' => 'objects.manage.view',       'module' => 'objects',        'title' => 'Объекты',           'icon' => 'bi-shop'],
            ['slug' => 'organizations.manage.view', 'module' => 'organizations',  'title' => 'Организации',       'icon' => 'bi-building'],
            ['slug' => 'geography.manage.view',     'module' => 'geography',      'title' => 'География',         'icon' => 'bi-globe-americas'],
            ['slug' => 'units.manage.view',         'module' => 'units',          'title' => 'Единицы измерения', 'icon' => 'bi-rulers'],
        ]
    ],
];
