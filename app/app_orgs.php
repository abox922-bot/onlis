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
      default:
      echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
      exit;
}

echo json_encode($result);
