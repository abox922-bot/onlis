<?php
  $modules_map = [
      'users' => [
          'folder'   => '_books_users',
          'sections' => [
              ['key' => 'users',  'title' => 'Пользователи',       'file' => 'users',  'default' => true],
              ['key' => 'groups', 'title' => 'Роли пользователей', 'file' => 'groups'],
          ],
      ],

      'geography' => [
          'folder'   => '_books_geography',
          'sections' => [
              ['key' => 'countries', 'title' => 'Страны',  'file' => 'countries', 'default' => true],
              ['key' => 'regions',   'title' => 'Регионы', 'file' => 'regions'],
              ['key' => 'cities',    'title' => 'Города',  'file' => 'cities'],
              ['key' => 'streets',   'title' => 'Улицы',   'file' => 'streets'],
          ],
      ],
  ];
