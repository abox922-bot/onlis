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
  ];
