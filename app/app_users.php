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
    // Список сотрудников: фильтр по организации + статус (actual/archived)
    case 'users_list':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_filter = $_POST['organization_id'] ?? '';
        $status = ($_POST['status'] ?? 'actual') === 'archived' ? 'archived' : 'actual';
        $actual_condition = $status === 'archived' ? 'u.actual IS NULL' : 'u.actual = 1';

        if ($organization_filter === 'none') {
            $stmt = fncQuery(
                "SELECT u.id AS user_id,
                        CONCAT_WS(' ', u.last_name, u.name) AS full_name
                 FROM users u
                 WHERE {$actual_condition} AND u.id NOT IN (
                     SELECT user_id FROM organization_staff WHERE date_end IS NULL
                 )
                 ORDER BY u.last_name, u.name"
            );
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($rows as $key => $row) {
                $rows[$key]['orgs_display'] = '';
            }
            $result = $rows;
            break;
        }

        $where_filter = '';
        $filter_params = [];
        if ($organization_filter !== '') {
            $where_filter = "AND u.id IN (
                SELECT user_id FROM organization_staff
                WHERE organization_id = ? AND date_end IS NULL
            )";
            $filter_params[] = (int)$organization_filter;
        }

        $stmt = fncQuery(
            "SELECT u.id AS user_id,
                    CONCAT_WS(' ', u.last_name, u.name) AS full_name,
                    GROUP_CONCAT(
                        CASE WHEN ot.is_individual
                            THEN CONCAT(ot.abbreviation, UNHEX('C2A0'), o.name)
                            ELSE CONCAT(ot.abbreviation, UNHEX('C2A0'), '«', o.name, '»')
                        END
                        ORDER BY (o.is_contractor OR o.is_bank), o.name
                        SEPARATOR ', '
                    ) AS orgs_display
             FROM users u
             LEFT JOIN organization_staff os ON os.user_id = u.id AND os.date_end IS NULL
             LEFT JOIN organizations o ON o.id = os.organization_id
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE {$actual_condition} {$where_filter}
             GROUP BY u.id
             ORDER BY u.last_name, u.name",
            $filter_params
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    // Данные для формы создания человека (список стран)
    case 'new_info':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt = fncQuery("SELECT id, name, phone_code, phone_mask FROM countries ORDER BY name");
        $result = ['countries' => $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []];
        break;

    // -------------------------------------------------------------------------
    // Создание человека
    case 'new':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $last_name        = fncValFind('user-last',            $params);
        $name             = fncValFind('user-name',             $params);
        $md_name          = fncValFind('user-md',                $params);
        $b_date           = fncValFind('user-bdate',             $params);
        $country_id       = (int)fncValFind('user-country-id',  $params);
        $phone_country_id = (int)fncValFind('phone-country-id', $params);
        $phone            = fncValFind('user-phone',             $params);
        $email            = fncValFind('user-email',             $params);

        if (!$last_name || !$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT id FROM users WHERE last_name = ? AND name = ? AND b_date = ?",
            [$last_name, $name, $b_date ?: null]
        );
        if ($stmt && $stmt->fetch()) {
            echo json_encode(['sccss' => false, 'msg' => 'Такой человек уже есть в системе']);
            exit;
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO users
                (is_active, country_id, phone_country_id, last_name, name, middle_name, b_date, phone, email, created_by)
             VALUES (0, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $country_id ?: null,
                $phone_country_id ?: null,
                $last_name,
                $name,
                $md_name ?: null,
                $b_date ?: null,
                $phone ?: null,
                $email ?: null,
                $user_id,
            ]
        );
        $result = $stmt ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()] : ['sccss' => false];
        break;

    // -------------------------------------------------------------------------
    // Личные данные человека
    case 'info_person':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $target_user_id = (int)($_POST['user_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT u.last_name, u.name, u.middle_name, u.b_date, u.phone, u.email, u.time_zone,
                    u.country_id, u.phone_country_id, c.phone_code, c.phone_mask
             FROM users u
             LEFT JOIN countries c ON c.id = u.phone_country_id
             WHERE u.id = ?",
            [$target_user_id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        $stmt = fncQuery("SELECT id, name, phone_code, phone_mask FROM countries ORDER BY name");
        $result['countries'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'upd_person':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $edit_user_id     = (int)fncValFind('user-id',            $params);
        $last_name        = fncValFind('user-last',                $params);
        $name             = fncValFind('user-name',                 $params);
        $md_name          = fncValFind('user-md',                   $params);
        $b_date           = fncValFind('user-bdate',                $params);
        $country_id       = (int)fncValFind('user-country-id',     $params);
        $phone_country_id = (int)fncValFind('phone-country-id',    $params);
        $phone            = fncValFind('user-phone',                $params);
        $email            = fncValFind('user-email',                $params);
        $time_zone        = fncValFind('user-time-zone',           $params);

        if (!$edit_user_id || !$last_name || !$name) {
            echo json_encode(['sccss' => false]);
            exit;
        }

        $stmt = fncQuery(
            "SELECT id FROM users WHERE id != ? AND last_name = ? AND name = ? AND b_date = ?",
            [$edit_user_id, $last_name, $name, $b_date ?: null]
        );
        if ($stmt && $stmt->fetch()) {
            echo json_encode(['sccss' => false, 'msg' => 'Такой человек уже есть в системе']);
            exit;
        }

        $stmt = fncQuery(
            "UPDATE users SET last_name = ?, name = ?, middle_name = ?, b_date = ?,
             country_id = ?, phone_country_id = ?, phone = ?, email = ?, time_zone = ?,
             updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$last_name, $name, $md_name ?: null, $b_date ?: null,
             $country_id ?: null, $phone_country_id ?: null,
             $phone ?: null, $email ?: null, $time_zone ?: null, $user_id, $edit_user_id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    // Доступ в систему
    case 'info_access':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $target_user_id = (int)($_POST['user_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT u.id AS user_id, u.is_active, ua.login
             FROM users u
             LEFT JOIN users_auth ua ON ua.user = u.id
             WHERE u.id = ?",
            [$target_user_id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------
    // Сохранение доступа. При отключении is_active — каскадом рвём сессии,
    // удаляем логин/пароль и уведомляем SSE-флагом (мгновенный разлогин клиента).
    case 'upd_access':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $edit_user_id = (int)fncValFind('user-id',   $params);
        $is_active     = (int)fncValFind('is-active', $params);
        $login         = fncValFind('login',          $params);
        $password      = fncValFind('password',      $params);

        if (!$edit_user_id) { echo json_encode(['sccss' => false]); exit; }

        fncQuery(
            "UPDATE users SET is_active = ?, updated_by = ?, updated_at = NOW() WHERE id = ?",
            [$is_active, $user_id, $edit_user_id]
        );

        if (!$is_active) {
            fncQuery(
                "UPDATE sessions SET session = NULL, cntrl = NULL, stop_time = NOW()
                 WHERE user = ? AND session IS NOT NULL",
                [$edit_user_id]
            );
            fncQuery("DELETE FROM users_auth WHERE user = ?", [$edit_user_id]);

            $flag_path = $_SERVER['DOCUMENT_ROOT'] . '/sse_cache/u_' . md5($edit_user_id) . '.flag';
            touch($flag_path);

            $result = ['sccss' => true];
            break;
        }

        if ($login) {
            $stmt = fncQuery("SELECT id FROM users_auth WHERE login = ? AND user != ?", [$login, $edit_user_id]);
            if ($stmt && $stmt->fetch()) {
                echo json_encode(['sccss' => false, 'msg' => 'Логин уже занят']);
                exit;
            }

            $stmt = fncQuery("SELECT id FROM users_auth WHERE user = ?", [$edit_user_id]);
            $exists = $stmt && $stmt->fetch();

            if ($password) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                if ($exists) {
                    fncQuery("UPDATE users_auth SET login = ?, password = ? WHERE user = ?", [$login, $hashed, $edit_user_id]);
                } else {
                    fncQuery("INSERT INTO users_auth (user, login, password) VALUES (?, ?, ?)", [$edit_user_id, $login, $hashed]);
                }
            } elseif ($exists) {
                fncQuery("UPDATE users_auth SET login = ? WHERE user = ?", [$login, $edit_user_id]);
            } else {
                fncQuery("INSERT INTO users_auth (user, login, password) VALUES (?, ?, ?)",
                    [$edit_user_id, $login, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
            }
        }

        $result = ['sccss' => true];
        break;

    // -------------------------------------------------------------------------
    // Активные организации человека — для предупреждения перед архивацией
    case 'active_organizations':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $target_user_id = (int)($_POST['user_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT o.name, ot.abbreviation, ot.is_individual
             FROM organization_staff os
             LEFT JOIN organizations o ON o.id = os.organization_id
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE os.user_id = ? AND os.date_end IS NULL
             ORDER BY o.name",
            [$target_user_id]
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $result = array_map(function($r) {
            return $r['is_individual']
                ? $r['abbreviation'] . ' ' . $r['name']
                : $r['abbreviation'] . ' «' . $r['name'] . '»';
        }, $rows);
        break;

    // -------------------------------------------------------------------------
    // Перенос в архив: actual = NULL, полное закрытие доступа и всех привязок,
    // SSE-уведомление о принудительном разлогине.
    case 'archive':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $archive_user_id = (int)fncValFind('user-id', $params);
        if (!$archive_user_id) { echo json_encode(['sccss' => false]); exit; }

        fncQuery(
            "UPDATE users SET actual = NULL, is_active = 0, updated_by = ?, updated_at = NOW() WHERE id = ?",
            [$user_id, $archive_user_id]
        );

        fncQuery(
            "UPDATE organization_staff SET date_end = CURDATE()
             WHERE user_id = ? AND date_end IS NULL",
            [$archive_user_id]
        );

        fncQuery(
            "UPDATE sessions SET session = NULL, cntrl = NULL, stop_time = NOW()
             WHERE user = ? AND session IS NOT NULL",
            [$archive_user_id]
        );

        fncQuery("DELETE FROM users_auth WHERE user = ?", [$archive_user_id]);

        $flag_path = $_SERVER['DOCUMENT_ROOT'] . '/sse_cache/u_' . md5($archive_user_id) . '.flag';
        touch($flag_path);

        $result = ['sccss' => true];
        break;

    // -------------------------------------------------------------------------
    // Восстановление из архива. Доступ и привязки НЕ восстанавливаются
    // автоматически — настраиваются заново вручную.
    case 'restore':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $restore_user_id = (int)fncValFind('user-id', $params);
        if (!$restore_user_id) { echo json_encode(['sccss' => false]); exit; }

        fncQuery(
            "UPDATE users SET actual = 1, updated_by = ?, updated_at = NOW() WHERE id = ?",
            [$user_id, $restore_user_id]
        );

        $result = ['sccss' => true];
        break;

    // -------------------------------------------------------------------------
    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
