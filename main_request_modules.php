<?php
require_once("./app/includes/request.php");

// Валидация модуля
$allowed_modules = array_keys($urls);
$module = mb_substr($_POST['module'] ?? '', 0, 20);

if (!in_array($module, $allowed_modules, true)) {
    http_response_code(400);
    die(json_encode(['sccss' => false, 'error' => 'Bad request']));
}

// Сессионные данные
$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id']         ?? '',
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '',
];

// Обработка файлов
$files_arr   = [];
$files_count = (int)($_POST['files_count'] ?? 0);

if ($files_count > 0) {
    for ($i = 0; $i < $files_count; $i++) {
        $f = "file{$i}";
        if (!isset($_FILES[$f])) continue;

        $ext      = new SplFileInfo($_FILES[$f]['name']);
        $nameOnly = $ext->getBasename('.' . $ext->getExtension());

        $files_arr[] = [
            'id'  => $i,
            'name' => $nameOnly,
            'ext' => $ext->getExtension(),
        ];
    }
}

// Сборка данных для запроса
$data = count($files_arr) > 0
    ? array_merge($ses_info, $_POST, ['files' => $files_arr])
    : array_merge($ses_info, $_POST);

// Отправка запроса в модуль
$result = send_request($data, $module);

// Сохранение загруженных файлов
if (!empty($result['new_files'])) {
    foreach ($result['new_files'] as $value) {
        $f_name = 'file' . $value['file_id'];
        if (isset($_FILES[$f_name]) && is_uploaded_file($_FILES[$f_name]['tmp_name'])) {
            move_uploaded_file($_FILES[$f_name]['tmp_name'], $value['name']);
        }
    }
}

// Удаление временных файлов
if (!empty($result['tmp_files'])) {
    foreach ($result['tmp_files'] as $value) {
        if (file_exists($value['name'])) {
            unlink($value['name']);
        }
    }
}

// Возврат результата
if (($_POST['return_data'] ?? 0) == 1) {
    echo json_encode($result);
}
