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

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO requisite_types
                (country_id, name, value_type, has_length_control, is_unique, is_bank_only, sort_order, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$country_id, $name, $value_type, $has_length_control, $is_unique, $is_bank_only, $sort_order, $user_id]
        );
        $result = $stmt ? ['sccss' => true, 'id' => $pdo->lastInsertId()] : ['sccss' => false];
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
    case 'organization_types_list':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $country_id = (int)($_POST['country_id'] ?? 0);
        if (!$country_id) { echo json_encode([]); exit; }
        $stmt = fncQuery(
            "SELECT id, name, abbreviation
             FROM organization_types
             WHERE country_id = ? AND is_active = 1
             ORDER BY name",
            [$country_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'organization_type_info':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT ot.id, ot.name, ot.abbreviation, ot.can_have_bank_account,
                    ot.is_individual, ot.is_active, c.name AS country_name
             FROM organization_types ot
             LEFT JOIN countries c ON c.id = ot.country_id
             WHERE ot.id = ?",
            [$id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------
    case 'new_organization_type':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $country_id            = (int)fncValFind('country-id',             $params);
        $name                  = fncValFind('type-name',                   $params);
        $abbreviation           = fncValFind('type-abbreviation',          $params);
        $can_have_bank_account = (int)fncValFind('can-have-bank-account',  $params);
        $is_individual          = (int)fncValFind('is-individual',         $params);

        if (!$country_id || !$name || !$abbreviation) {
            echo json_encode(['sccss' => false]);
            exit;
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO organization_types
                (country_id, name, abbreviation, can_have_bank_account, is_individual, created_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$country_id, $name, $abbreviation, $can_have_bank_account, $is_individual, $user_id]
        );
        $result = $stmt ? ['sccss' => true, 'id' => $pdo->lastInsertId()] : ['sccss' => false];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_type':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id                     = (int)fncValFind('item-id',                $params);
        $name                   = fncValFind('type-name',                   $params);
        $abbreviation           = fncValFind('type-abbreviation',           $params);
        $can_have_bank_account  = (int)fncValFind('can-have-bank-account',  $params);
        $is_individual          = (int)fncValFind('is-individual',          $params);
        $is_active              = (int)fncValFind('type-is-active',         $params);

        if (!$id || !$name || !$abbreviation) {
            echo json_encode(['sccss' => false]);
            exit;
        }
        $stmt = fncQuery(
            "UPDATE organization_types
             SET name = ?, abbreviation = ?, can_have_bank_account = ?, is_individual = ?,
                 updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$name, $abbreviation, $can_have_bank_account, $is_individual, $user_id, $id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'organization_type_requisites_list':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_type_id = (int)($_POST['organization_type_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT otr.id, rt.name, otr.exact_length, otr.is_required
             FROM organization_type_requisites otr
             LEFT JOIN requisite_types rt ON rt.id = otr.requisite_type_id
             WHERE otr.organization_type_id = ?
             ORDER BY otr.sort_order, rt.name",
            [$organization_type_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'organization_type_requisites_available':
        // Реквизиты страны ОПФ, ещё не добавленные в набор
        if (!fncCan($perms, 'organizations')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_type_id = (int)($_POST['id'] ?? 0);

        $stmt = fncQuery("SELECT country_id FROM organization_types WHERE id = ?", [$organization_type_id]);
        $type_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        $country_id = $type_row['country_id'] ?? 0;

        $stmt = fncQuery(
            "SELECT id, name, has_length_control
             FROM requisite_types
             WHERE country_id = ?
               AND id NOT IN (
                   SELECT requisite_type_id FROM organization_type_requisites
                   WHERE organization_type_id = ?
               )
             ORDER BY name",
            [$country_id, $organization_type_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'new_organization_type_requisite':
        if (!fncCan($perms, 'organizations')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $organization_type_id = (int)fncValFind('item-id',       $params);
        $requisite_type_id    = (int)fncValFind('requisite-id',  $params);
        $is_required          = (int)fncValFind('is-required',   $params);
        $exact_length         = fncValFind('exact-length',       $params);
        $exact_length         = $exact_length === '' ? null : (int)$exact_length;

        if (!$organization_type_id || !$requisite_type_id) {
            echo json_encode(['sccss' => false]);
            exit;
        }

        $stmt = fncQuery(
            "INSERT INTO organization_type_requisites
                (organization_type_id, requisite_type_id, exact_length, is_required, created_by)
             VALUES (?, ?, ?, ?, ?)",
            [$organization_type_id, $requisite_type_id, $exact_length, $is_required, $user_id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'del_organization_type_requisite':
        if (!fncCan($perms, 'organizations')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)fncValFind('id', $params);
        if (!$id) { echo json_encode(['sccss' => false]); exit; }
        $stmt = fncQuery("DELETE FROM organization_type_requisites WHERE id = ?", [$id]);
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'new_organization_info':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_type  = $_POST['org_type'] ?? 'my';
        $countries = [];
        $types     = [];

        $stmt = fncQuery(
            "SELECT id, name FROM countries ORDER BY name"
        );
        $countries = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // Для банков — только ОПФ с can_have_bank_account = 1
        if ($org_type === 'bank') {
            $stmt = fncQuery(
                "SELECT id, name, abbreviation, country_id
                 FROM organization_types
                 WHERE is_active = 1 AND can_have_bank_account = 1
                 ORDER BY name"
            );
        } else {
            $stmt = fncQuery(
                "SELECT id, name, abbreviation, country_id
                 FROM organization_types
                 WHERE is_active = 1
                 ORDER BY name"
            );
        }
        $types  = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $result = ['countries' => $countries, 'types' => $types];
        break;

    // -------------------------------------------------------------------------
    case 'organizations_list':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_type = $_POST['org_type'] ?? 'my';

        if ($org_type === 'my') {
            $where = "o.is_contractor = 0 AND o.is_bank = 0";
        } elseif ($org_type === 'contractor') {
            $where = "o.is_contractor = 1";
        } elseif ($org_type === 'bank') {
            $where = "o.is_bank = 1";
        } else {
            $where = "o.is_contractor = 0 AND o.is_bank = 0";
        }

        $stmt = fncQuery(
            "SELECT o.id, o.name, o.short_name, ot.abbreviation, ot.is_individual
             FROM organizations o
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE {$where} AND o.is_active = 1
             ORDER BY o.name"
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rows as $key => $row) {
            if ($row['is_individual']) {
                $rows[$key]['display_name'] = $row['abbreviation'] . ' ' . $row['name'];
            } else {
                $rows[$key]['display_name'] = $row['abbreviation'] . ' «' . $row['name'] . '»';
            }
        }
        $result = $rows;
        break;

    // -------------------------------------------------------------------------
    case 'organization_info':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);

        $stmt = fncQuery(
            "SELECT o.id, o.name, o.short_name, o.phone, o.email, o.website,
                    o.is_contractor, o.is_bank, o.is_active,
                    ot.id AS type_id, ot.name AS type_name, ot.abbreviation, ot.is_individual,
                    c.id AS country_id, c.name AS country_name,
                    c.phone_code, c.phone_mask
             FROM organizations o
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             LEFT JOIN countries c ON c.id = o.country_id
             WHERE o.id = ?",
            [$id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        $stmt_reqs = fncQuery(
            "SELECT orq.requisite_type_id AS id, rt.name, orq.value,
                    rt.value_type, rt.is_unique, rt.has_length_control,
                    otr.exact_length, otr.is_required
             FROM organization_requisites orq
             LEFT JOIN requisite_types rt ON rt.id = orq.requisite_type_id
             LEFT JOIN organization_type_requisites otr
                   ON otr.requisite_type_id = orq.requisite_type_id
                  AND otr.organization_type_id = ?
             WHERE orq.organization_id = ?",
            [(int)$result['type_id'], $id]
        );
        $result['reqs'] = $stmt_reqs ? $stmt_reqs->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'organization_type_requisites_new_form':
        // Реквизиты ОПФ для формы создания организации
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $type_id  = (int)($_POST['type_id'] ?? 0);
        $org_type = $_POST['org_type'] ?? 'my';

        $stmt = fncQuery(
            "SELECT otr.id, otr.requisite_type_id, otr.exact_length, otr.is_required,
                    rt.name, rt.value_type, rt.has_length_control, rt.is_unique, rt.is_bank_only
             FROM organization_type_requisites otr
             LEFT JOIN requisite_types rt ON rt.id = otr.requisite_type_id
             WHERE otr.organization_type_id = ?
             ORDER BY otr.sort_order, rt.name",
            [$type_id]
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // Если не банк — исключаем банковские реквизиты
        if ($org_type !== 'bank') {
            $rows = array_filter($rows, function($r) {
                return !$r['is_bank_only'];
            });
        }
        $result = array_values($rows);
        break;

    // -------------------------------------------------------------------------
    case 'new_organization':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_name      = fncValFind('org-name',          $params);
        $type_id       = (int)fncValFind('org-type-id',  $params);
        $country_id    = (int)fncValFind('org-country-id', $params);
        $is_contractor = (int)fncValFind('org-is-contractor', $params);
        $is_bank       = (int)fncValFind('org-is-bank',   $params);
        $reqs_list     = fncValFind('reqs-list',          $params);

        if (!$org_name || !$type_id || !$country_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        // Проверка уникальности реквизитов
        if (is_array($reqs_list)) {
            foreach ($reqs_list as $req) {
                if (!empty($req['uniq']) && !empty($req['value'])) {
                    $stmt = fncQuery(
                        "SELECT id FROM organization_requisites
                         WHERE requisite_type_id = ? AND value = ?",
                        [(int)$req['id'], $req['value']]
                    );
                    if ($stmt && $stmt->fetch()) {
                        echo json_encode([
                            'sccss' => false,
                            'msg'   => 'Реквизит уже используется другой организацией'
                        ]);
                        exit;
                    }
                }
            }
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO organizations
                (organization_type_id, country_id, name, is_contractor, is_bank, created_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$type_id, $country_id, $org_name, $is_contractor, $is_bank, $user_id]
        );

        if (!$stmt) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка при создании организации']);
            exit;
        }

        $org_id = (int)$pdo->lastInsertId();

        // Сохраняем реквизиты
        if (is_array($reqs_list)) {
            foreach ($reqs_list as $req) {
                if (!empty($req['value'])) {
                    fncQuery(
                        "INSERT INTO organization_requisites
                            (organization_id, requisite_type_id, value, created_by)
                         VALUES (?, ?, ?, ?)",
                        [$org_id, (int)$req['id'], $req['value'], $user_id]
                    );
                }
            }
        }

        $result = ['sccss' => true, 'id' => $org_id];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_main':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id       = (int)fncValFind('item-id',  $params);
        $org_name = fncValFind('org-name',       $params);
        $phone    = fncValFind('org-phone',       $params);
        $email    = fncValFind('org-email',       $params);
        $website  = fncValFind('org-website',     $params);
        $reqs_list = fncValFind('reqs-list',      $params);

        if (!$id || !$org_name) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        // Проверка уникальности реквизитов
        if (is_array($reqs_list)) {
            foreach ($reqs_list as $req) {
                if (!empty($req['uniq']) && !empty($req['value'])) {
                    $stmt = fncQuery(
                        "SELECT id FROM organization_requisites
                         WHERE requisite_type_id = ? AND value = ? AND organization_id != ?",
                        [(int)$req['id'], $req['value'], $id]
                    );
                    if ($stmt && $stmt->fetch()) {
                        echo json_encode([
                            'sccss' => false,
                            'msg'   => 'Реквизит уже используется другой организацией'
                        ]);
                        exit;
                    }
                }
            }
        }

        // Получаем abbreviation и is_individual для обновления short_name
        $stmt_type = fncQuery(
            "SELECT ot.abbreviation, ot.is_individual
             FROM organizations o
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE o.id = ?",
            [$id]
        );
        $type_row   = $stmt_type ? $stmt_type->fetch(PDO::FETCH_ASSOC) : null;
        $short_name = null;
        if ($type_row) {
            $short_name = $type_row['is_individual']
                ? $type_row['abbreviation'] . ' ' . $org_name
                : $type_row['abbreviation'] . ' «' . $org_name . '»';
        }

        $stmt = fncQuery(
            "UPDATE organizations
             SET name = ?, short_name = ?, phone = ?, email = ?, website = ?,
                 updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$org_name, $short_name, $phone ?: null, $email ?: null, $website ?: null, $user_id, $id]
        );

        if (!$stmt) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка при сохранении']);
            exit;
        }

        // Обновляем реквизиты
        if (is_array($reqs_list)) {
            foreach ($reqs_list as $req) {
                if (empty($req['value'])) continue;
                // Upsert: обновить если есть, вставить если нет
                $stmt_check = fncQuery(
                    "SELECT id FROM organization_requisites
                     WHERE organization_id = ? AND requisite_type_id = ?",
                    [$id, (int)$req['id']]
                );
                if ($stmt_check && $stmt_check->fetch()) {
                    fncQuery(
                        "UPDATE organization_requisites
                         SET value = ?, updated_by = ?, updated_at = NOW()
                         WHERE organization_id = ? AND requisite_type_id = ?",
                        [$req['value'], $user_id, $id, (int)$req['id']]
                    );
                } else {
                    fncQuery(
                        "INSERT INTO organization_requisites
                            (organization_id, requisite_type_id, value, created_by)
                         VALUES (?, ?, ?, ?)",
                        [$id, (int)$req['id'], $req['value'], $user_id]
                    );
                }
            }
        }

        $result = ['sccss' => true];
        break;

    // -------------------------------------------------------------------------
    case 'organization_info_address':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);

        // Данные организации + страна
        $stmt = fncQuery(
            "SELECT o.region_id, o.city_id, o.street_id, o.house, o.office,
                    c.id AS country_id, c.name AS country_name
             FROM organizations o
             LEFT JOIN countries c ON c.id = o.country_id
             WHERE o.id = ?",
            [$id]
        );
        $org = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        $country_id = (int)($org['country_id'] ?? 0);

        // Все регионы страны
        $stmt = fncQuery(
            "SELECT id, name FROM regions WHERE country = ? ORDER BY name",
            [$country_id]
        );
        $regions = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // Все города всех регионов страны
        $cities = [];
        foreach ($regions as $region) {
            $stmt = fncQuery(
                "SELECT id, name FROM cities WHERE region = ? ORDER BY name",
                [(int)$region['id']]
            );
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($rows as $city) {
                $cities[] = ['id' => $city['id'], 'name' => $city['name'], 'region' => $region['id']];
            }
        }

        // Все улицы всех городов страны
        $streets = [];
        foreach ($cities as $city) {
            $stmt = fncQuery(
                "SELECT s.id, CONCAT_WS(' ', st.name, s.name) AS name
                 FROM streets s
                 LEFT JOIN streets_types st ON st.id = s.type
                 WHERE s.city = ?
                 ORDER BY s.name",
                [(int)$city['id']]
            );
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($rows as $street) {
                $streets[] = ['id' => $street['id'], 'name' => $street['name'], 'city' => $city['id']];
            }
        }

        $result = [
            'country_name' => $org['country_name'] ?? '',
            'region_id'    => $org['region_id'],
            'city_id'      => $org['city_id'],
            'street_id'    => $org['street_id'],
            'house'        => $org['house'],
            'office'       => $org['office'],
            'regions'      => $regions,
            'cities'       => $cities,
            'streets'      => $streets,
        ];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_address':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id        = (int)fncValFind('item-id',   $params);
        $region_id = (int)fncValFind('adr-reg',   $params);
        $city_id   = (int)fncValFind('adr-city',  $params);
        $street_id = (int)fncValFind('adr-str',   $params);
        $house     = fncValFind('adr-house',       $params);
        $office    = fncValFind('adr-office',      $params);

        if (!$id) { echo json_encode(['sccss' => false]); exit; }

        $stmt = fncQuery(
            "UPDATE organizations
             SET region_id = ?, city_id = ?, street_id = ?, house = ?, office = ?,
                 updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $region_id ?: null,
                $city_id   ?: null,
                $street_id ?: null,
                $house     ?: null,
                $office    ?: null,
                $user_id,
                $id
            ]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'organization_bank_accounts_list':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id = (int)($_POST['id'] ?? 0);

        $stmt = fncQuery(
            "SELECT oba.id, oba.account_number, oba.is_active,
                    o.name AS bank_name, ot.abbreviation
             FROM organization_bank_accounts oba
             LEFT JOIN organizations o  ON o.id  = oba.bank_id
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE oba.organization_id = ?
             ORDER BY oba.is_active DESC, o.name, oba.account_number",
            [$org_id]
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // Для каждого банка подгружаем его банковские реквизиты (БИК, корсчёт)
        foreach ($rows as $key => $row) {
            $stmt_reqs = fncQuery(
                "SELECT rt.name, orq.value
                 FROM organization_requisites orq
                 LEFT JOIN requisite_types rt ON rt.id = orq.requisite_type_id
                 WHERE orq.organization_id = ? AND rt.is_bank_only = 1",
                [(int)$row['id'] /* bank_id нужен */]
            );
            // Исправляем — берём bank_id
        }

        // Правильный запрос с bank_id для реквизитов
        $result = [];
        foreach ($rows as $row) {
            $stmt_reqs = fncQuery(
                "SELECT rt.name, orq.value
                 FROM organization_requisites orq
                 LEFT JOIN requisite_types rt ON rt.id = orq.requisite_type_id
                 WHERE orq.organization_id = ? AND rt.is_bank_only = 1",
                [(int)$row['id']]
            );
            // bank_id берём из oba
            $result[] = $row;
        }

        // Переписываю правильно одним запросом
        $stmt = fncQuery(
            "SELECT oba.id, oba.account_number, oba.is_active, oba.bank_id,
                    o.name AS bank_name, ot.abbreviation
             FROM organization_bank_accounts oba
             LEFT JOIN organizations o  ON o.id  = oba.bank_id
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE oba.organization_id = ?
             ORDER BY oba.is_active DESC, o.name, oba.account_number",
            [$org_id]
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rows as $key => $row) {
            $stmt_reqs = fncQuery(
                "SELECT rt.name, orq.value
                 FROM organization_requisites orq
                 LEFT JOIN requisite_types rt ON rt.id = orq.requisite_type_id
                 WHERE orq.organization_id = ? AND rt.is_bank_only = 1",
                [(int)$row['bank_id']]
            );
            $rows[$key]['reqs'] = $stmt_reqs ? $stmt_reqs->fetchAll(PDO::FETCH_ASSOC) : [];
        }
        $result = $rows;
        break;

    // -------------------------------------------------------------------------
    case 'toggle_bank_account_active':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $acc_id   = (int)fncValFind('acc-id',    $params);
        $is_active = (int)fncValFind('is-active', $params);
        if (!$acc_id) { echo json_encode(['sccss' => false]); exit; }
        $stmt  = fncQuery(
            "UPDATE organization_bank_accounts
             SET is_active = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$is_active, $user_id, $acc_id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'new_organization_bank_account':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id        = (int)fncValFind('org-id',     $params);
        $bank_id       = (int)fncValFind('acc-bank',   $params);
        $account_number = fncValFind('acc-number',     $params);

        if (!$org_id || !$bank_id || !$account_number) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните все поля']);
            exit;
        }

        // Проверка уникальности номера счёта
        $stmt = fncQuery(
            "SELECT id FROM organization_bank_accounts WHERE account_number = ?",
            [$account_number]
        );
        if ($stmt && $stmt->fetch()) {
            echo json_encode(['sccss' => false, 'msg' => 'Номер счёта уже существует']);
            exit;
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO organization_bank_accounts
                (organization_id, bank_id, account_number, created_by)
             VALUES (?, ?, ?, ?)",
            [$org_id, $bank_id, $account_number, $user_id]
        );
        $result = $stmt ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()] : ['sccss' => false];
        break;

    // -------------------------------------------------------------------------
    case 'organization_info_accs':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt = fncQuery(
            "SELECT o.id, o.name, ot.abbreviation
             FROM organizations o
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE o.is_bank = 1 AND o.is_active = 1
             ORDER BY o.name"
        );
        $result = ['banks' => $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []];
        break;

    // -------------------------------------------------------------------------
    case 'organization_departments_list':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT id, name, is_active FROM organization_departments
             WHERE organization_id = ? ORDER BY is_active DESC, name",
            [$org_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'organization_department_info':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT d.id, d.name, d.is_active,
                    (SELECT COUNT(id) FROM organization_staff
                     WHERE department_id = d.id AND date_end IS NULL) AS staff_count
             FROM organization_departments d WHERE d.id = ?",
            [$id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------
    case 'new_organization_department':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id = (int)fncValFind('org-id',   $params);
        $name   = fncValFind('dep-name',       $params);
        if (!$org_id || !$name) {
            echo json_encode(['sccss' => false]);
            exit;
        }
        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO organization_departments (organization_id, name, created_by)
             VALUES (?, ?, ?)",
            [$org_id, $name, $user_id]
        );
        $result = $stmt ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()] : ['sccss' => false];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_department':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id        = (int)fncValFind('dep-id',     $params);
        $name      = fncValFind('dep-name',         $params);
        $is_active = (int)fncValFind('dep-active',  $params);
        if (!$id || !$name) {
            echo json_encode(['sccss' => false]);
            exit;
        }
        $stmt = fncQuery(
            "UPDATE organization_departments
             SET name = ?, is_active = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$name, $is_active, $user_id, $id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'organization_positions_list':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT id, name, is_active FROM organization_positions
             WHERE organization_id = ? ORDER BY is_active DESC, name",
            [$org_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'organization_position_info':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT p.id, p.name, p.is_active,
                    (SELECT COUNT(id) FROM organization_staff
                     WHERE position_id = p.id AND date_end IS NULL) AS staff_count
             FROM organization_positions p WHERE p.id = ?",
            [$id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------
    case 'new_organization_position':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id = (int)fncValFind('org-id',  $params);
        $name   = fncValFind('pos-name',      $params);
        if (!$org_id || !$name) {
            echo json_encode(['sccss' => false]);
            exit;
        }
        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO organization_positions (organization_id, name, created_by)
             VALUES (?, ?, ?)",
            [$org_id, $name, $user_id]
        );
        $result = $stmt ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()] : ['sccss' => false];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_position':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id        = (int)fncValFind('pos-id',     $params);
        $name      = fncValFind('pos-name',         $params);
        $is_active = (int)fncValFind('pos-active',  $params);
        if (!$id || !$name) {
            echo json_encode(['sccss' => false]);
            exit;
        }
        $stmt = fncQuery(
            "UPDATE organization_positions
             SET name = ?, is_active = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$name, $is_active, $user_id, $id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'organization_staff_list':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT os.id,
                    CONCAT_WS(' ', u.last_name, u.name) AS name,
                    op.name AS title,
                    IF(os.phone IS NULL,
                        (SELECT phone_code FROM countries WHERE id = u.country_id),
                        (SELECT phone_code FROM countries WHERE id = o.country_id)) AS phone_code,
                    IF(os.phone IS NULL, u.phone, os.phone) AS phone,
                    IF(os.email IS NULL, u.email, os.email) AS email
             FROM organization_staff os
             LEFT JOIN users u ON u.id = os.user_id
             LEFT JOIN organizations o ON o.id = os.organization_id
             LEFT JOIN organization_positions op ON op.id = os.position_id
             WHERE os.organization_id = ? AND os.date_end IS NULL
             ORDER BY u.last_name, u.name",
            [$org_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------
    case 'new_staff_org_info':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT c.phone_code, c.phone_mask
             FROM organizations o
             LEFT JOIN countries c ON c.id = o.country_id
             WHERE o.id = ?",
            [$org_id]
        );
        $org = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        // Пользователи не состоящие в этой организации
        $stmt = fncQuery(
            "SELECT u.id, CONCAT_WS(' ', u.last_name, u.name) AS name
             FROM users u
             WHERE u.id NOT IN (
                 SELECT user_id FROM organization_staff
                 WHERE organization_id = ? AND date_end IS NULL
             )
             ORDER BY u.last_name, u.name",
            [$org_id]
        );
        $org['users'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $result = $org;
        break;

    // -------------------------------------------------------------------------
    case 'new_organization_staff':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id    = (int)fncValFind('org-id',      $params);
        $last_name = fncValFind('staff-last',        $params);
        $name      = fncValFind('staff-name',        $params);
        $md_name   = fncValFind('staff-md',          $params);
        $b_date    = fncValFind('staff-bdate',       $params);
        $phone     = fncValFind('staff-phone',       $params);
        $email     = fncValFind('staff-email',       $params);

        if (!$org_id || !$last_name || !$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        // Проверка дубля
        $stmt = fncQuery(
            "SELECT id FROM users WHERE last_name = ? AND name = ? AND b_date = ?",
            [$last_name, $name, $b_date]
        );
        if ($stmt && $stmt->fetch()) {
            echo json_encode(['sccss' => false, 'msg' => 'Сотрудник с такими данными уже есть в системе']);
            exit;
        }

        // Получаем country_id организации
        $stmt = fncQuery("SELECT country_id FROM organizations WHERE id = ?", [$org_id]);
        $org_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO users (is_active, country_id, last_name, name, middle_name, b_date, phone, email, created_by)
             VALUES (0, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$org_row['country_id'] ?? null, $last_name, $name, $md_name ?: null,
             $b_date ?: null, $phone ?: null, $email ?: null, $user_id]
        );
        if (!$stmt) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка при создании пользователя']);
            exit;
        }
        $new_user_id = (int)$pdo->lastInsertId();

        fncQuery(
            "INSERT INTO organization_staff (organization_id, user_id, date_start, created_by)
             VALUES (?, ?, CURDATE(), ?)",
            [$org_id, $new_user_id, $user_id]
        );
        $result = ['sccss' => true];
        break;

    // -------------------------------------------------------------------------
    case 'add_staff_to_organization':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $org_id      = (int)fncValFind('org-id',  $params);
        $user_id_add = (int)fncValFind('user-id', $params);
        if (!$org_id || !$user_id_add) {
            echo json_encode(['sccss' => false]);
            exit;
        }
        $stmt = fncQuery(
            "INSERT INTO organization_staff (organization_id, user_id, date_start, created_by)
             VALUES (?, ?, CURDATE(), ?)",
            [$org_id, $user_id_add, $user_id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'organization_staff_info_main':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $st_id = (int)($_POST['st_id'] ?? 0);
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
            [$st_id]
        );
        $data = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        // Должности организации
        $stmt = fncQuery(
            "SELECT id, name FROM organization_positions
             WHERE organization_id = (SELECT organization_id FROM organization_staff WHERE id = ?)
             AND is_active = 1 ORDER BY name",
            [$st_id]
        );
        $data['titles'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // Отделы организации
        $stmt = fncQuery(
            "SELECT id, name FROM organization_departments
             WHERE organization_id = (SELECT organization_id FROM organization_staff WHERE id = ?)
             AND is_active = 1 ORDER BY name",
            [$st_id]
        );
        $data['deps'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $result = $data;
        break;

    // -------------------------------------------------------------------------
    case 'organization_staff_info_person':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $st_id = (int)($_POST['st_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT os.user_id, u.last_name, u.name, u.middle_name AS md_name,
                    u.b_date, u.phone, u.email, u.time_zone,
                    (SELECT phone_code FROM countries WHERE id = u.country_id) AS phone_code,
                    (SELECT phone_mask FROM countries WHERE id = u.country_id) AS phone_mask
             FROM organization_staff os
             LEFT JOIN users u ON u.id = os.user_id
             WHERE os.id = ?",
            [$st_id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_staff_main':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $st_id       = (int)fncValFind('st-id',           $params);
        $position_id = (int)fncValFind('staff-title',     $params);
        $dep_id      = (int)fncValFind('staff-dep',       $params);
        $w_email     = fncValFind('work-email',            $params);
        $w_phone     = fncValFind('work-phone',            $params);
        $w_phone_ext = fncValFind('work-phone-ext',        $params);
        $is_contact  = (int)fncValFind('contact',         $params);
        if (!$st_id) { echo json_encode(['sccss' => false]); exit; }
        $stmt = fncQuery(
            "UPDATE organization_staff
             SET position_id = ?, department_id = ?, email = ?, phone = ?,
                 phone_extension = ?, is_contact = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$position_id ?: null, $dep_id ?: null, $w_email ?: null,
             $w_phone ?: null, $w_phone_ext ?: null, $is_contact, $user_id, $st_id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_staff_person':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $st_id     = (int)fncValFind('st-id',       $params);
        $last_name = fncValFind('staff-last',         $params);
        $name      = fncValFind('staff-name',         $params);
        $md_name   = fncValFind('staff-md',           $params);
        $b_date    = fncValFind('staff-bdate',        $params);
        $phone     = fncValFind('staff-phone',        $params);
        $email     = fncValFind('staff-email',        $params);
        $time_zone = fncValFind('staff-time-zone',    $params);
        if (!$st_id || !$last_name || !$name) {
            echo json_encode(['sccss' => false]);
            exit;
        }

        // Получаем user_id
        $stmt = fncQuery("SELECT user_id FROM organization_staff WHERE id = ?", [$st_id]);
        $st_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        $usr_id = (int)($st_row['user_id'] ?? 0);

        // Проверка дубля
        $stmt = fncQuery(
            "SELECT id FROM users WHERE id != ? AND last_name = ? AND name = ? AND b_date = ?",
            [$usr_id, $last_name, $name, $b_date]
        );
        if ($stmt && $stmt->fetch()) {
            echo json_encode(['sccss' => false, 'msg' => 'Сотрудник с такими данными уже есть в системе']);
            exit;
        }

        $stmt = fncQuery(
            "UPDATE users SET last_name = ?, name = ?, middle_name = ?, b_date = ?,
             phone = ?, email = ?, time_zone = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ?",
            [$last_name, $name, $md_name ?: null, $b_date ?: null,
             $phone ?: null, $email ?: null, $time_zone ?: null, $user_id, $usr_id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    // -------------------------------------------------------------------------
    case 'dismiss_organization_staff':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $st_id = (int)fncValFind('st-id', $params);
        if (!$st_id) { echo json_encode(['sccss' => false]); exit; }

        // Получаем user_id сотрудника
        $stmt = fncQuery("SELECT user_id FROM organization_staff WHERE id = ?", [$st_id]);
        $st_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!$st_row) { echo json_encode(['sccss' => false]); exit; }
        $dismissed_user_id = (int)$st_row['user_id'];

        // 1. Закрываем запись сотрудника
        fncQuery("UPDATE organization_staff SET date_end = CURDATE() WHERE id = ?", [$st_id]);

        // 2. Блокируем пользователя
        fncQuery(
            "UPDATE users SET is_active = 0, actual = NULL, updated_by = ?, updated_at = NOW() WHERE id = ?",
            [$user_id, $dismissed_user_id]
        );

        // 3. Сбрасываем активные сессии
        fncQuery(
            "UPDATE sessions SET session = NULL, cntrl = NULL, stop_time = NOW()
             WHERE user = ? AND session IS NOT NULL",
            [$dismissed_user_id]
        );

        // 4. Удаляем учётные данные
        fncQuery("DELETE FROM users_auth WHERE user = ?", [$dismissed_user_id]);

        $result = ['sccss' => true];
        break;

    // -------------------------------------------------------------------------
    case 'organization_staff_info_access':
        if (!fncCan($perms, 'organizations.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $st_id = (int)($_POST['st_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT u.id, u.is_active, ua.login
             FROM organization_staff os
             LEFT JOIN users u ON u.id = os.user_id
             LEFT JOIN users_auth ua ON ua.user = u.id
             WHERE os.id = ?",
            [$st_id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------
    case 'upd_organization_staff_access':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $st_id     = (int)fncValFind('st-id',     $params);
        $is_active = (int)fncValFind('is-active',  $params);
        $login     = fncValFind('login',            $params);

        if (!$st_id) { echo json_encode(['sccss' => false]); exit; }

        // Получаем user_id
        $stmt = fncQuery("SELECT user_id FROM organization_staff WHERE id = ?", [$st_id]);
        $st_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!$st_row) { echo json_encode(['sccss' => false]); exit; }
        $usr_id = (int)$st_row['user_id'];

        // Обновляем is_active
        fncQuery(
            "UPDATE users SET is_active = ?, updated_by = ?, updated_at = NOW() WHERE id = ?",
            [$is_active, $user_id, $usr_id]
        );

        // Обновляем логин если передан
        if ($login) {
            // Проверяем уникальность логина
            $stmt = fncQuery(
                "SELECT id FROM users_auth WHERE login = ? AND user != ?",
                [$login, $usr_id]
            );
            if ($stmt && $stmt->fetch()) {
                echo json_encode(['sccss' => false, 'msg' => 'Логин уже занят']);
                exit;
            }
            // Обновляем или создаём запись в users_auth
            $stmt = fncQuery("SELECT id FROM users_auth WHERE user = ?", [$usr_id]);
            if ($stmt && $stmt->fetch()) {
                fncQuery("UPDATE users_auth SET login = ? WHERE user = ?", [$login, $usr_id]);
            } else {
                fncQuery(
                    "INSERT INTO users_auth (user, login, password) VALUES (?, ?, ?)",
                    [$usr_id, $login, password_hash('', PASSWORD_DEFAULT)]
                );
            }
        }

        $result = ['sccss' => true];
        break;

    // -------------------------------------------------------------------------
    case 'reset_staff_password':
        if (!fncCan($perms, 'organizations.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $st_id = (int)fncValFind('st-id', $params);
        if (!$st_id) { echo json_encode(['sccss' => false]); exit; }

        $stmt = fncQuery("SELECT user_id FROM organization_staff WHERE id = ?", [$st_id]);
        $st_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!$st_row) { echo json_encode(['sccss' => false]); exit; }
        $usr_id = (int)$st_row['user_id'];

        // Генерируем новый пароль
        $new_password = strtoupper(bin2hex(random_bytes(4)));
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = fncQuery("SELECT id FROM users_auth WHERE user = ?", [$usr_id]);
        if ($stmt && $stmt->fetch()) {
            fncQuery("UPDATE users_auth SET password = ? WHERE user = ?", [$hashed, $usr_id]);
        } else {
            echo json_encode(['sccss' => false, 'msg' => 'У сотрудника нет учётных данных']);
            exit;
        }

        $result = ['sccss' => true, 'password' => $new_password];
        break;

      // -------------------------------------------------------------------------
      default:
      echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
      exit;
}

echo json_encode($result);
