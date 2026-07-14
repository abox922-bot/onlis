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
    case 'users_list':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_filter = $_POST['organization_id'] ?? '';

        if ($organization_filter === 'none') {
            // Люди без единой активной привязки
            $stmt = fncQuery(
                "SELECT u.id AS user_id,
                        CONCAT_WS(' ', u.last_name, u.name) AS full_name
                 FROM users u
                 WHERE u.actual = 1 AND u.id NOT IN (
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
             JOIN organization_staff os ON os.user_id = u.id AND os.date_end IS NULL
             JOIN organizations o ON o.id = os.organization_id
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE u.actual = 1 {$where_filter}
             GROUP BY u.id
             ORDER BY u.last_name, u.name",
            $filter_params
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'users_organizations_filter':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt = fncQuery(
            "SELECT o.id, o.name, ot.abbreviation, ot.is_individual, o.is_contractor, o.is_bank
             FROM organizations o
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE o.is_active = 1
             ORDER BY (o.is_contractor OR o.is_bank), o.name"
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as $key => $row) {
            $rows[$key]['display_name'] = $row['is_individual']
                ? $row['abbreviation'] . ' ' . $row['name']
                : $row['abbreviation'] . ' «' . $row['name'] . '»';
        }
        $result = $rows;
        break;

    // -------------------------------------------------------------------------
    // Данные для формы создания человека без организации
    case 'new_user_info':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt = fncQuery("SELECT id, name, phone_code, phone_mask FROM countries ORDER BY name");
        $result = ['countries' => $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []];
        break;

    // -------------------------------------------------------------------------
    // Создание человека без привязки к организации
    case 'new_user':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $last_name        = fncValFind('user-last',           $params);
        $name             = fncValFind('user-name',            $params);
        $md_name          = fncValFind('user-md',              $params);
        $b_date           = fncValFind('user-bdate',           $params);
        $country_id       = (int)fncValFind('user-country-id', $params);
        $phone_country_id = (int)fncValFind('phone-country-id',$params);
        $phone            = fncValFind('user-phone',           $params);
        $email            = fncValFind('user-email',           $params);

        if (!$last_name || !$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        // Проверка дубля
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
    // Список сотрудников конкретной организации (вкладка в карточке организации)
    case 'organization_staff_list':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_id = (int)($_POST['organization_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT os.id AS organization_staff_id,
                    CONCAT_WS(' ', u.last_name, u.name) AS name,
                    op.name AS title,
                    IF(os.phone IS NULL,
                        (SELECT phone_code FROM countries WHERE id = u.phone_country_id),
                        (SELECT phone_code FROM countries WHERE id = o.country_id)) AS phone_code,
                    IF(os.phone IS NULL, u.phone, os.phone) AS phone,
                    IF(os.email IS NULL, u.email, os.email) AS email
             FROM organization_staff os
             LEFT JOIN users u ON u.id = os.user_id
             LEFT JOIN organizations o ON o.id = os.organization_id
             LEFT JOIN organization_positions op ON op.id = os.position_id
             WHERE os.organization_id = ? AND os.date_end IS NULL
             ORDER BY u.last_name, u.name",
            [$organization_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    // Данные для формы "Добавить сотрудника" из карточки организации
    case 'new_staff_org_info':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_id = (int)($_POST['organization_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT c.phone_code, c.phone_mask
             FROM organizations o
             LEFT JOIN countries c ON c.id = o.country_id
             WHERE o.id = ?",
            [$organization_id]
        );
        $org = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        $stmt = fncQuery(
            "SELECT u.id, CONCAT_WS(' ', u.last_name, u.name) AS name
             FROM users u
             WHERE u.actual = 1 AND u.id NOT IN (
                 SELECT user_id FROM organization_staff
                 WHERE organization_id = ? AND date_end IS NULL
             )
             ORDER BY u.last_name, u.name",
            [$organization_id]
        );
        $org['users'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $result = $org;
        break;

    // -------------------------------------------------------------------------
    // Создание нового человека сразу с привязкой к организации
    case 'new_organization_staff':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_id  = (int)fncValFind('organization-id',  $params);
        $last_name        = fncValFind('staff-last',            $params);
        $name             = fncValFind('staff-name',            $params);
        $md_name          = fncValFind('staff-md',              $params);
        $b_date           = fncValFind('staff-bdate',           $params);
        $country_id       = (int)fncValFind('user-country-id',  $params);
        $phone_country_id = (int)fncValFind('phone-country-id', $params);
        $phone            = fncValFind('staff-phone',           $params);
        $email            = fncValFind('staff-email',           $params);

        if (!$organization_id || !$last_name || !$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT id FROM users WHERE last_name = ? AND name = ? AND b_date = ?",
            [$last_name, $name, $b_date ?: null]
        );
        if ($stmt && $stmt->fetch()) {
            echo json_encode(['sccss' => false, 'msg' => 'Сотрудник с такими данными уже есть в системе']);
            exit;
        }

        // Если страна не передана явно — по умолчанию страна организации
        if (!$country_id) {
            $stmt = fncQuery("SELECT country_id FROM organizations WHERE id = ?", [$organization_id]);
            $org_row    = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            $country_id = (int)($org_row['country_id'] ?? 0);
        }
        if (!$phone_country_id) {
            $phone_country_id = $country_id;
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
        if (!$stmt) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка при создании пользователя']);
            exit;
        }
        $new_user_id = (int)$pdo->lastInsertId();

        fncQuery(
            "INSERT INTO organization_staff (organization_id, user_id, date_start, created_by)
             VALUES (?, ?, CURDATE(), ?)",
            [$organization_id, $new_user_id, $user_id]
        );
        $result = ['sccss' => true, 'id' => $new_user_id];
        break;

    // -------------------------------------------------------------------------
    // Привязка уже существующего в системе человека к организации
    case 'add_staff_to_organization':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_id = (int)fncValFind('organization-id', $params);
        $add_user_id      = (int)fncValFind('user-id',         $params);
        if (!$organization_id || !$add_user_id) {
            echo json_encode(['sccss' => false]);
            exit;
        }
        $stmt = fncQuery(
            "INSERT INTO organization_staff (organization_id, user_id, date_start, created_by)
             VALUES (?, ?, CURDATE(), ?)",
            [$organization_id, $add_user_id, $user_id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    // Вкладка "Основная" карточки сотрудника (данные трудоустройства)
    case 'organization_staff_info_main':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_staff_id = (int)($_POST['organization_staff_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT os.position_id AS title, os.department_id AS dep,
                    os.phone AS w_phone, os.phone_extension AS w_phone_more,
                    os.email AS w_email, os.is_contact AS contact,
                    u.is_active AS is_user,
                    (SELECT phone_code FROM countries WHERE id = o.country_id) AS w_code,
                    (SELECT phone_mask FROM countries WHERE id = o.country_id) AS w_mask
             FROM organization_staff os
             LEFT JOIN organizations o ON o.id = os.organization_id
             LEFT JOIN users u ON u.id = os.user_id
             WHERE os.id = ?",
            [$organization_staff_id]
        );
        $data = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        $stmt = fncQuery(
            "SELECT id, name FROM organization_positions
             WHERE organization_id = (SELECT organization_id FROM organization_staff WHERE id = ?)
             AND is_active = 1 ORDER BY name",
            [$organization_staff_id]
        );
        $data['titles'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmt = fncQuery(
            "SELECT id, name FROM organization_departments
             WHERE organization_id = (SELECT organization_id FROM organization_staff WHERE id = ?)
             AND is_active = 1 ORDER BY name",
            [$organization_staff_id]
        );
        $data['deps'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $result = $data;
        break;

    // -------------------------------------------------------------------------
    // Вкладка "Личная" карточки сотрудника (данные человека)
    case 'organization_staff_info_person':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_staff_id = (int)($_POST['organization_staff_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT os.user_id, u.last_name, u.name, u.middle_name AS md_name,
                    u.b_date, u.phone, u.email, u.time_zone,
                    u.country_id, u.phone_country_id,
                    (SELECT phone_code FROM countries WHERE id = u.phone_country_id) AS phone_code,
                    (SELECT phone_mask FROM countries WHERE id = u.phone_country_id) AS phone_mask
             FROM organization_staff os
             LEFT JOIN users u ON u.id = os.user_id
             WHERE os.id = ?",
            [$organization_staff_id]
        );
        $data = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        $stmt = fncQuery("SELECT id, name FROM countries ORDER BY name");
        $data['countries'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $result = $data;
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_staff_main':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_staff_id = (int)fncValFind('organization-staff-id', $params);
        $position_id           = (int)fncValFind('staff-title',            $params);
        $dep_id                = (int)fncValFind('staff-dep',              $params);
        $w_email                = fncValFind('work-email',                  $params);
        $w_phone                = fncValFind('work-phone',                  $params);
        $w_phone_ext            = fncValFind('work-phone-ext',              $params);
        $is_contact             = (int)fncValFind('contact',               $params);
        if (!$organization_staff_id) { echo json_encode(['sccss' => false]); exit; }
        $stmt = fncQuery(
            "UPDATE organization_staff
             SET position_id = ?, department_id = ?, email = ?, phone = ?,
                 phone_extension = ?, is_contact = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$position_id ?: null, $dep_id ?: null, $w_email ?: null,
             $w_phone ?: null, $w_phone_ext ?: null, $is_contact, $user_id, $organization_staff_id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_staff_person':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_staff_id = (int)fncValFind('organization-staff-id', $params);
        $last_name              = fncValFind('staff-last',                  $params);
        $name                   = fncValFind('staff-name',                  $params);
        $md_name                = fncValFind('staff-md',                    $params);
        $b_date                 = fncValFind('staff-bdate',                 $params);
        $country_id             = (int)fncValFind('user-country-id',       $params);
        $phone_country_id       = (int)fncValFind('phone-country-id',      $params);
        $phone                  = fncValFind('staff-phone',                 $params);
        $email                  = fncValFind('staff-email',                 $params);
        $time_zone              = fncValFind('staff-time-zone',            $params);

        if (!$organization_staff_id || !$last_name || !$name) {
            echo json_encode(['sccss' => false]);
            exit;
        }

        $stmt = fncQuery("SELECT user_id FROM organization_staff WHERE id = ?", [$organization_staff_id]);
        $st_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        $edit_user_id = (int)($st_row['user_id'] ?? 0);

        $stmt = fncQuery(
            "SELECT id FROM users WHERE id != ? AND last_name = ? AND name = ? AND b_date = ?",
            [$edit_user_id, $last_name, $name, $b_date ?: null]
        );
        if ($stmt && $stmt->fetch()) {
            echo json_encode(['sccss' => false, 'msg' => 'Сотрудник с такими данными уже есть в системе']);
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
    case 'dismiss_organization_staff':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_staff_id = (int)fncValFind('organization-staff-id', $params);
        if (!$organization_staff_id) { echo json_encode(['sccss' => false]); exit; }

        $stmt = fncQuery("SELECT user_id FROM organization_staff WHERE id = ?", [$organization_staff_id]);
        $st_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!$st_row) { echo json_encode(['sccss' => false]); exit; }
        $dismissed_user_id = (int)$st_row['user_id'];

        fncQuery("UPDATE organization_staff SET date_end = CURDATE() WHERE id = ?", [$organization_staff_id]);

        fncQuery(
            "UPDATE users SET is_active = 0, actual = NULL, updated_by = ?, updated_at = NOW() WHERE id = ?",
            [$user_id, $dismissed_user_id]
        );

        fncQuery(
            "UPDATE sessions SET session = NULL, cntrl = NULL, stop_time = NOW()
             WHERE user = ? AND session IS NOT NULL",
            [$dismissed_user_id]
        );

        fncQuery("DELETE FROM users_auth WHERE user = ?", [$dismissed_user_id]);

        $result = ['sccss' => true];
        break;

    // -------------------------------------------------------------------------
    case 'organization_staff_info_access':
        if (!fncCan($perms, 'users.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_staff_id = (int)($_POST['organization_staff_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT u.id, u.is_active, ua.login
             FROM organization_staff os
             LEFT JOIN users u ON u.id = os.user_id
             LEFT JOIN users_auth ua ON ua.user = u.id
             WHERE os.id = ?",
            [$organization_staff_id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_staff_access':
        if (!fncCan($perms, 'users.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_staff_id = (int)fncValFind('organization-staff-id', $params);
        $is_active              = (int)fncValFind('is-active',              $params);
        $login                  = fncValFind('login',                       $params);
        $password               = fncValFind('password',                   $params);

        if (!$organization_staff_id) { echo json_encode(['sccss' => false]); exit; }

        $stmt = fncQuery("SELECT user_id FROM organization_staff WHERE id = ?", [$organization_staff_id]);
        $st_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!$st_row) { echo json_encode(['sccss' => false]); exit; }
        $edit_user_id = (int)$st_row['user_id'];

        fncQuery(
            "UPDATE users SET is_active = ?, updated_by = ?, updated_at = NOW() WHERE id = ?",
            [$is_active, $user_id, $edit_user_id]
        );

        if ($login) {
            $stmt = fncQuery(
                "SELECT id FROM users_auth WHERE login = ? AND user != ?",
                [$login, $edit_user_id]
            );
            if ($stmt && $stmt->fetch()) {
                echo json_encode(['sccss' => false, 'msg' => 'Логин уже занят']);
                exit;
            }

            $stmt = fncQuery("SELECT id FROM users_auth WHERE user = ?", [$edit_user_id]);
            $exists = $stmt && $stmt->fetch();

            if ($password) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                if ($exists) {
                    fncQuery("UPDATE users_auth SET login = ?, password = ? WHERE user = ?",
                        [$login, $hashed, $edit_user_id]);
                } else {
                    fncQuery("INSERT INTO users_auth (user, login, password) VALUES (?, ?, ?)",
                        [$edit_user_id, $login, $hashed]);
                }
            } elseif ($exists) {
                fncQuery("UPDATE users_auth SET login = ? WHERE user = ?", [$login, $edit_user_id]);
            } else {
                // Логин без пароля — создаём запись с непроходным хешем, пароль зададут позже
                fncQuery("INSERT INTO users_auth (user, login, password) VALUES (?, ?, ?)",
                    [$edit_user_id, $login, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
            }
        }

        $result = ['sccss' => true];
        break;

    // -------------------------------------------------------------------------
    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
