<?php
  $modules_map = [
      'main' => [
          'folder'   => '_main',
          'sections' => [
              ['key' => 'main',  'title' => 'Главная',  'file' => 'main',  'default' => true],
          ],
      ],

      'users' => [
          'folder'   => '_books_users',
          'sections' => [
              ['key' => 'users',  'slug' => 'users.manage.view',  'title' => 'Пользователи',  'file' => 'users',  'default' => true],
          ],
      ],


      'geography' => [
          'folder'   => '_books_geo',
          'sections' => [
              ['key' => 'countries',  'slug' => 'geography.manage.view',  'title' => 'Страны',  'file' => 'countries', 'default' => true],
              ['key' => 'regions',    'slug' => 'geography.manage.view',  'title' => 'Регионы', 'file' => 'regions'],
              ['key' => 'cities',     'slug' => 'geography.manage.view',  'title' => 'Города',  'file' => 'cities'],
              ['key' => 'streets',    'slug' => 'geography.manage.view',  'title' => 'Улицы',   'file' => 'streets'],
          ],
      ],

      'organizations' => [
          'folder'   => '_books_orgs',
          'sections' => [
              ['key' => 'my_organizations',   'slug' => 'organizations.manage.view', 'title' => 'Мои организации', 'file' => 'my_organizations',   'default' => true],
              ['key' => 'contractors',        'slug' => 'organizations.manage.view', 'title' => 'Контрагенты',     'file' => 'contractors'],
              ['key' => 'banks',              'slug' => 'organizations.manage.view', 'title' => 'Банки',           'file' => 'banks'],
              ['key' => 'organization_types', 'slug' => 'organizations.manage.view', 'title' => 'ОПФ',             'file' => 'organization_types'],
              ['key' => 'requisite_types',    'slug' => 'organizations.manage.view', 'title' => 'Реквизиты',       'file' => 'requisite_types'],
          ],
      ],

      'objects' => [
          'folder'   => '_books_objs',
          'sections' => [
              ['key' => 'objects',        'slug' => 'objects.manage.view', 'title' => 'Объекты',          'file' => 'objects',      'default' => true],
              ['key' => 'objects_groups', 'slug' => 'objects.manage.view', 'title' => 'Группы объектов',  'file' => 'objects_groups'],
              ['key' => 'object_types',   'slug' => 'objects.manage.view', 'title' => 'Типы объектов',    'file' => 'object_types'],
          ],
      ],

      'units' => [
          'folder'   => '_books_units',
          'sections' => [
              ['key' => 'units', 'slug' => 'units.manage.view', 'title' => 'Единицы измерения', 'file' => 'units', 'default' => true],
          ],
      ],

      'nomenclature' => [
          'folder'   => '_books_noms',
          'sections' => [
              ['key' => 'nomenclature',         'slug' => 'nomenclature.manage.view', 'title' => 'Номенклатура',  'file' => 'nomenclature',       'default' => true],
              ['key' => 'semi_finished',        'slug' => 'nomenclature.manage.view', 'title' => 'ПФ',            'file' => 'semi_finished'],
              ['key' => 'nomenclature_groups',  'slug' => 'nomenclature.manage.view', 'title' => 'Группы',        'file' => 'nomenclature_groups'],
          ],
      ],

      'products' => [
          'folder'   => '_books_products',
          'sections' => [
              ['key' => 'products',         'slug' => 'products.manage.view', 'title' => 'Товары',          'file' => 'products',       'default' => true],
              ['key' => 'products_groups',  'slug' => 'products.manage.view', 'title' => 'Группы товаров',  'file' => 'products_groups'],
          ],
      ],

  ];
