<?php
  $modules_map = [
      'users' => [
          'folder'   => '_books_users',
          'sections' => [
              ['key' => 'users',  'slug' => 'geography',  'title' => 'Пользователи',  'file' => 'users',  'default' => true],
              ['key' => 'rules',  'slug' => 'geography',  'title' => 'Права доступа', 'file' => 'rules'],
          ],
      ],

      'geography' => [
          'folder'   => '_books_geo',
          'sections' => [
              ['key' => 'countries',  'slug' => 'geography',  'title' => 'Страны',  'file' => 'countries', 'default' => true],
              ['key' => 'regions',    'slug' => 'geography',  'title' => 'Регионы', 'file' => 'regions'],
              ['key' => 'cities',     'slug' => 'geography',  'title' => 'Города',  'file' => 'cities'],
              ['key' => 'streets',    'slug' => 'geography',  'title' => 'Улицы',   'file' => 'streets'],
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

  ];
