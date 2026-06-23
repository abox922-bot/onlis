<?php
require_once('./includes/fncs.php');
require_once('./includes/request.php');

header('Content-Type: application/json');

// =============================================================================
// Проверка сессии
// =============================================================================
$cookie = $_POST['_onlis_id'] ?? '';
$token  = $_POST['x_token']   ?? '';

if (!$cookie || !$token) {
    echo json_encode(['sccss' => false]);
    exit;
}

$ses_check = send_request([
    '_onlis_id' => $cookie,
    'x_token'   => $token,
    'action'    => 'in_cntrl',
], 'main');

if (!$ses_check || empty($ses_check['sccss'])) {
    echo json_encode(['sccss' => false]);
    exit;
}

$user_id = (int)($ses_check['user'] ?? 0);
$perms   = $ses_check['rules'] ?? [];

// =============================================================================
// Роутинг
// =============================================================================
$action = $_POST['action'] ?? '';
$params = isset($_POST['params']) ? json_decode($_POST['params'], true) : [];
if (!is_array($params)) {
    $params = [];
}

$result = [];

switch ($action) {

    // =========================================================================
    // COUNTRIES
    // =========================================================================

    case 'countries_list':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt   = fncQuery("SELECT id, name, full_name FROM countries ORDER BY name");
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------

    case 'country_info':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id     = (int)($_POST['id'] ?? 0);
        $stmt   = fncQuery(
            "SELECT id, name, full_name, phone_code, phone_mask
             FROM countries
             WHERE id = ?",
            [$id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------

    case 'new_country':
        if (!fncCan($perms, 'geography.edit')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $name = trim(fncValFind('cntr-name', $params) ?? '');

        if (!$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указано название']);
            exit;
        }

        $stmt = fncQuery(
            "INSERT INTO countries (name, created_by, updated_by) VALUES (?, ?, ?)",
            [$name, $user_id, $user_id]
        );

        if (!$stmt) {
            echo json_encode(['sccss' => false]);
            exit;
        }

        global $pdo;
        echo json_encode(['sccss' => true, 'id' => (int)$pdo->lastInsertId()]);
        exit;

    // -------------------------------------------------------------------------

    case 'upd_country':
        if (!fncCan($perms, 'geography.edit')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id        = (int)(fncValFind('item-id',    $params) ?? 0);
        $full_name = trim(fncValFind('cntry-fname', $params) ?? '');
        $name      = trim(fncValFind('cntry-name',  $params) ?? '');
        $code      = trim(fncValFind('cntry-code',  $params) ?? '');
        $mask      = trim(fncValFind('cntry-mask',  $params) ?? '');

        if (!$id || !$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка данных']);
            exit;
        }

        $stmt = fncQuery(
            "UPDATE countries SET name = ?, full_name = ?, phone_code = ?, phone_mask = ?, updated_by = ? WHERE id = ?",
            [$name, $full_name, $code, $mask, $user_id, $id]
        );

        echo json_encode(['sccss' => (bool)$stmt]);
        exit;

    // =========================================================================
    // REGIONS
    // =========================================================================

    case 'regions_list':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $country = (int)($_POST['country'] ?? 0);
        $stmt    = fncQuery(
            "SELECT id, name FROM regions WHERE country = ? ORDER BY name",
            [$country]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------

    case 'region_info':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT r.id, r.name, r.reg_code, r.timezone, c.name AS country_name
             FROM regions r
             JOIN countries c ON c.id = r.country
             WHERE r.id = ?",
            [$id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------

    case 'new_region':
        if (!fncCan($perms, 'geography.edit')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $name    = trim(fncValFind('reg-name', $params) ?? '');
        $country = (int)(fncValFind('country',  $params) ?? 0);

        if (!$name || !$country) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка данных']);
            exit;
        }

        $stmt = fncQuery(
            "INSERT INTO regions (country, name, created_by, updated_by) VALUES (?, ?, ?, ?)",
            [$country, $name, $user_id, $user_id]
        );

        if (!$stmt) {
            echo json_encode(['sccss' => false]);
            exit;
        }

        global $pdo;
        echo json_encode(['sccss' => true, 'id' => (int)$pdo->lastInsertId()]);
        exit;

    // -------------------------------------------------------------------------

    case 'upd_region':
        if (!fncCan($perms, 'geography.edit')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id       = (int)(fncValFind('item-id',  $params) ?? 0);
        $name     = trim(fncValFind('reg-name',  $params) ?? '');
        $reg_code = trim(fncValFind('reg-code',  $params) ?? '');
        $timezone = fncValFind('timezone', $params);

        if (!$id || !$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка данных']);
            exit;
        }

        $stmt = fncQuery(
            "UPDATE regions SET name = ?, reg_code = ?, timezone = ?, updated_by = ? WHERE id = ?",
            [$name, $reg_code, $timezone, $user_id, $id]
        );

        echo json_encode(['sccss' => (bool)$stmt]);
        exit;

    // =========================================================================
    // CITIES
    // =========================================================================

    case 'countries_regs_list':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt_countries = fncQuery("SELECT id, name FROM countries ORDER BY name");
        $stmt_regions   = fncQuery("SELECT id, country, name FROM regions ORDER BY name");

        $result = [
            'countries' => $stmt_countries ? $stmt_countries->fetchAll(PDO::FETCH_ASSOC) : [],
            'regions'   => $stmt_regions   ? $stmt_regions->fetchAll(PDO::FETCH_ASSOC)   : [],
        ];
        break;

    // -------------------------------------------------------------------------

    case 'cities_list':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $country = (int)($_POST['country'] ?? 0);
        $region  = (int)($_POST['region']  ?? 0);

        $stmt   = fncQuery(
            "SELECT id, name FROM cities WHERE country = ? AND region = ? ORDER BY name",
            [$country, $region]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------

    case 'city_info':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT ci.id, ci.name, c.name AS country_name, r.name AS region_name
             FROM cities ci
             JOIN countries c ON c.id = ci.country
             JOIN regions   r ON r.id = ci.region
             WHERE ci.id = ?",
            [$id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    // -------------------------------------------------------------------------

    case 'new_city':
        if (!fncCan($perms, 'geography.edit')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $name    = trim(fncValFind('city-name', $params) ?? '');
        $country = (int)(fncValFind('country',   $params) ?? 0);
        $region  = (int)(fncValFind('region',    $params) ?? 0);

        if (!$name || !$country || !$region) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка данных']);
            exit;
        }

        $stmt = fncQuery(
            "INSERT INTO cities (country, region, name, created_by, updated_by) VALUES (?, ?, ?, ?, ?)",
            [$country, $region, $name, $user_id, $user_id]
        );

        echo json_encode(['sccss' => (bool)$stmt]);
        exit;

    // -------------------------------------------------------------------------

    case 'upd_city':
        if (!fncCan($perms, 'geography.edit')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id   = (int)(fncValFind('item-id',   $params) ?? 0);
        $name = trim(fncValFind('city-name', $params) ?? '');

        if (!$id || !$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка данных']);
            exit;
        }

        $stmt = fncQuery("UPDATE cities SET name = ?, updated_by = ? WHERE id = ?", [$name, $user_id, $id]);

        echo json_encode(['sccss' => (bool)$stmt]);
        exit;

    // =========================================================================
    // STREETS
    // =========================================================================

    case 'cities_list_for_streets':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt   = fncQuery(
            "SELECT ci.id, ci.name, ci.region, ci.country, r.name AS reg_name
             FROM cities ci
             JOIN regions r ON r.id = ci.region
             ORDER BY ci.name"
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------

    case 'streets_list':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $city   = (int)($_POST['city'] ?? 0);
        $stmt   = fncQuery(
            "SELECT s.id, CONCAT(st.short_name, ' ', s.name) AS name
             FROM streets s
             JOIN streets_types st ON st.id = s.type
             WHERE s.city = ?
             ORDER BY s.name",
            [$city]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------

    case 'streets_types_list':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $stmt   = fncQuery("SELECT id, name FROM streets_types ORDER BY name");
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    // -------------------------------------------------------------------------

    case 'street_info':
        if (!fncCan($perms, 'geography')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id     = (int)($_POST['id'] ?? 0);
        $stmt_s = fncQuery("SELECT id, name, type FROM streets WHERE id = ?", [$id]);
        $street = $stmt_s ? ($stmt_s->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        $stmt_t = fncQuery("SELECT id, name FROM streets_types ORDER BY name");
        $types  = $stmt_t ? $stmt_t->fetchAll(PDO::FETCH_ASSOC) : [];
        $result = array_merge($street, ['types' => $types]);
        break;

    // -------------------------------------------------------------------------

    case 'new_street':
        if (!fncCan($perms, 'geography.edit')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $name    = trim(fncValFind('street-name',   $params) ?? '');
        $type    = (int)(fncValFind('street-type',  $params) ?? 0);
        $city    = (int)(fncValFind('city',         $params) ?? 0);
        $region  = (int)(fncValFind('region',       $params) ?? 0);
        $country = (int)(fncValFind('country',      $params) ?? 0);

        if (!$name || !$type || !$city || !$region || !$country) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка данных']);
            exit;
        }

        $stmt = fncQuery(
            "INSERT INTO streets (city, region, country, type, name, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$city, $region, $country, $type, $name, $user_id, $user_id]
        );

        echo json_encode(['sccss' => (bool)$stmt]);
        exit;

    // -------------------------------------------------------------------------

    case 'upd_street':
        if (!fncCan($perms, 'geography.edit')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id   = (int)(fncValFind('item-id',     $params) ?? 0);
        $name = trim(fncValFind('street-name', $params) ?? '');
        $type = (int)(fncValFind('street-type', $params) ?? 0);

        if (!$id || !$name || !$type) {
            echo json_encode(['sccss' => false, 'msg' => 'Ошибка данных']);
            exit;
        }

        $stmt = fncQuery("UPDATE streets SET name = ?, type = ?, updated_by = ? WHERE id = ?", [$name, $type, $user_id, $id]);

        echo json_encode(['sccss' => (bool)$stmt]);
        exit;

    // =========================================================================

    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
