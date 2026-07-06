<?php
require_once('./includes/fncs.php');
require_once('./includes/request.php');

header('Content-Type: application/json');

$cookie = $_POST['_onlis_id'] ?? '';
$token  = $_POST['x_token']   ?? '';

if (!$cookie || !$token) { echo json_encode(['sccss' => false]); exit; }

$ses_check = send_request([
    '_onlis_id' => $cookie,
    'x_token'   => $token,
    'action'    => 'in_cntrl'
], 'main');

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

    case 'objects_list':
        if (!fncCan($perms, 'objects.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt = fncQuery(
            "SELECT `objects`.`id`, `objects`.`name`, `objects`.`is_active`,
                    `object_types`.`name` AS `type_name`,
                    `organizations`.`short_name`, `organizations`.`name` AS `org_name`
             FROM `objects`
             LEFT JOIN `object_types` ON `object_types`.`id` = `objects`.`type_id`
             LEFT JOIN `organizations` ON `organizations`.`id` = `objects`.`organization_id`
             ORDER BY `objects`.`is_active` DESC, `objects`.`name`"
        );
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['org_display'] = $row['short_name'] ?: $row['org_name'];
                $result[] = $row;
            }
        }
        break;

    case 'new_object':
        if (!fncCan($perms, 'objects.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_id = fncValFind('organization_id', $params);
        $type_id         = fncValFind('type_id', $params);
        $name            = fncValFind('name', $params);

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO `objects` (`organization_id`, `type_id`, `name`, `created_by`)
             VALUES (?, ?, ?, ?)",
            [$organization_id, $type_id, $name, $user_id]
        );
        $result = $stmt
            ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()]
            : ['sccss' => false, 'msg' => 'Не удалось создать объект'];
        break;

    case 'object_types_list':
        if (!fncCan($perms, 'objects.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt = fncQuery(
            "SELECT `object_types`.`id`, `object_types`.`name`, `object_types`.`organization_id`,
                    `object_types`.`is_active`, `organizations`.`short_name`, `organizations`.`name` AS `org_name`
             FROM `object_types`
             LEFT JOIN `organizations` ON `organizations`.`id` = `object_types`.`organization_id`
             ORDER BY `object_types`.`organization_id` IS NULL DESC, `object_types`.`is_active` DESC, `object_types`.`name`"
        );
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['org_display'] = $row['organization_id'] ? ($row['short_name'] ?: $row['org_name']) : 'Системный';
                $result[] = $row;
            }
        }
        break;

    case 'new_object_type':
        if (!fncCan($perms, 'objects.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $name            = fncValFind('name', $params);
        $organization_id = fncValFind('organization_id', $params);

        if (!$organization_id) {
            if (!fncCan($perms, 'objects')) {
                echo json_encode(['sccss' => false, 'msg' => 'Выберите организацию']);
                exit;
            }
            $organization_id = null;
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO `object_types` (`organization_id`, `name`, `created_by`)
             VALUES (?, ?, ?)",
            [$organization_id, $name, $user_id]
        );
        $result = $stmt
            ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()]
            : ['sccss' => false, 'msg' => 'Не удалось создать тип'];
        break;

    case 'object_type_info':
        if (!fncCan($perms, 'objects.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT `object_types`.`id`, `object_types`.`name`, `object_types`.`is_active`,
                    `object_types`.`organization_id`, `organizations`.`short_name`, `organizations`.`name` AS `org_name`
             FROM `object_types`
             LEFT JOIN `organizations` ON `organizations`.`id` = `object_types`.`organization_id`
             WHERE `object_types`.`id` = ?",
            [$id]
        );
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($row) {
            $row['org_display'] = $row['organization_id'] ? ($row['short_name'] ?: $row['org_name']) : null;
            $result = $row;
        }
        break;

    case 'upd_object_type':
        if (!fncCan($perms, 'objects.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)fncValFind('id', $params);

        $check = fncQuery("SELECT `organization_id` FROM `object_types` WHERE `id` = ?", [$id]);
        $row = $check ? $check->fetch(PDO::FETCH_ASSOC) : null;
        if (!$row || $row['organization_id'] === null) {
            echo json_encode(['sccss' => false, 'msg' => 'Системный тип нельзя редактировать']);
            exit;
        }

        $name      = fncValFind('name', $params);
        $is_active = fncValFind('is_active', $params);

        $stmt = fncQuery(
            "UPDATE `object_types` SET `name` = ?, `is_active` = ?, `updated_at` = NOW(), `updated_by` = ?
             WHERE `id` = ?",
            [$name, $is_active, $user_id, $id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
