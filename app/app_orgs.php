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

    // -------------------------------------------------------------------------
    case 'requisite_types_list':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $country_id = (int)($_POST['country_id'] ?? 0);
        if (!$country_id) { echo json_encode([]); exit; }
        $stmt = fncQuery(
            "SELECT id, name, value_type, has_length_control, is_unique, is_bank_only, sort_order
             FROM requisite_types
             WHERE country_id = ?
             ORDER BY sort_order, name",
            [$country_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'requisite_type_info':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT rt.id, rt.name, rt.value_type, rt.has_length_control,
                    rt.is_unique, rt.is_bank_only, rt.sort_order, c.name AS country_name
             FROM requisite_types rt
             LEFT JOIN countries c ON c.id = rt.country_id
             WHERE rt.id = ?",
            [$id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------
    case 'new_requisite_type':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $country_id         = (int)fncValFind('country-id',         $params);
        $name               = fncValFind('req-name',                $params);
        $value_type         = fncValFind('req-value-type',          $params);
        $has_length_control = (int)fncValFind('has-length-control', $params);
        $is_unique          = (int)fncValFind('is-unique',          $params);
        $is_bank_only       = (int)fncValFind('is-bank-only',       $params);
        $sort_order         = (int)fncValFind('sort-order',         $params);

        if (!$country_id || !$name || !in_array($value_type, ['text', 'digits', 'date'])) {
            echo json_encode(['sccss' => false]);
            exit;
        }

        $stmt = fncQuery(
            "INSERT INTO requisite_types
                (country_id, name, value_type, has_length_control, is_unique, is_bank_only, sort_order, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$country_id, $name, $value_type, $has_length_control, $is_unique, $is_bank_only, $sort_order, $user_id]
        );
        if ($stmt) {
            $result = ['sccss' => true, 'id' => fncLastId()];
        } else {
            $result = ['sccss' => false];
        }
        break;

    // -------------------------------------------------------------------------
    case 'upd_requisite_type':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id                 = (int)fncValFind('item-id',            $params);
        $name               = fncValFind('req-name',                $params);
        $value_type         = fncValFind('req-value-type',          $params);
        $has_length_control = (int)fncValFind('has-length-control', $params);
        $is_unique          = (int)fncValFind('is-unique',          $params);
        $is_bank_only       = (int)fncValFind('is-bank-only',       $params);
        $sort_order         = (int)fncValFind('sort-order',         $params);

        if (!$id || !$name || !in_array($value_type, ['text', 'digits', 'date'])) {
            echo json_encode(['sccss' => false]);
            exit;
        }

        $stmt = fncQuery(
            "UPDATE requisite_types
             SET name = ?, value_type = ?, has_length_control = ?, is_unique = ?,
                 is_bank_only = ?, sort_order = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$name, $value_type, $has_length_control, $is_unique, $is_bank_only, $sort_order, $user_id, $id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
