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

        foreach ($rows as &$row) {
            if ($row['is_individual']) {
                $row['display_name'] = $row['abbreviation'] . ' ' . $row['name'];
            } else {
                $row['display_name'] = $row['abbreviation'] . ' «' . $row['name'] . '»';
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
    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
