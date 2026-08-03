<?php
require_once('./includes/fncs.php');
require_once('./includes/request.php');

header('Content-Type: application/json');

$cookie = $_POST['_onlis_id'] ?? '';
$token  = $_POST['x_token']   ?? '';

if (!$cookie || !$token) { echo json_encode(['sccss' => false]); exit; }

$ses_check = fncApiAuth($cookie, $token);

if (!$ses_check || empty($ses_check['sccss'])) {
    echo json_encode(['sccss' => false]);
    exit;
}

$user_id = (int)($ses_check['user'] ?? 0);
$perms   = $ses_check['rules'] ?? [];
$action  = $_POST['action'] ?? '';
$params  = isset($_POST['params']) ? json_decode($_POST['params'], true) : [];
if (!is_array($params)) $params = [];

$result = [];

switch ($action) {

    case 'units_list':
        if (!fncCan($perms, 'units.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt = fncQuery("SELECT `id`, `name`, `short_name`, `is_float` FROM `units` ORDER BY `name`");
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    case 'unit_info':
        if (!fncCan($perms, 'units.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery("SELECT `id`, `name`, `short_name`, `is_float` FROM `units` WHERE `id` = ?", [$id]);
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    case 'new_unit':
        if (!fncCan($perms, 'units.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $name       = fncValFind('name', $params);
        $short_name = fncValFind('short_name', $params);
        $is_float   = (int)fncValFind('is_float', $params);

        if (!$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Укажите название']);
            exit;
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO `units` (`name`, `short_name`, `is_float`) VALUES (?, ?, ?)",
            [$name, $short_name ?: null, $is_float]
        );
        $result = $stmt
            ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()]
            : ['sccss' => false, 'msg' => 'Не удалось создать единицу измерения'];
        break;

    case 'upd_unit':
        if (!fncCan($perms, 'units.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id         = (int)fncValFind('id', $params);
        $name       = fncValFind('name', $params);
        $short_name = fncValFind('short_name', $params);
        $is_float   = (int)fncValFind('is_float', $params);

        if (!$id || !$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Укажите название']);
            exit;
        }

        $stmt = fncQuery(
            "UPDATE `units` SET `name` = ?, `short_name` = ?, `is_float` = ? WHERE `id` = ?",
            [$name, $short_name ?: null, $is_float, $id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
