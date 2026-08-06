<?php
// app_noms.php обслуживает ДВА модуля — Номенклатура и Товары —
// с общими таблицами (nomenclature_groups, nomenclature). Поэтому каждый
// case проверяет права по обеим RBAC-веткам (nomenclature.* ИЛИ products.*)
// как альтернативу — это архитектурное решение, не ошибка/недоделанный рефакторинг.
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

    case 'groups_list':
        if (!fncCan($perms, 'nomenclature.manage.view') && !fncCan($perms, 'products.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $type   = fncValFind('type', $params) ?: ($_POST['type'] ?? '');
        $status = fncValFind('status', $params) ?: ($_POST['status'] ?? 'active');

        $where  = [];
        $binds  = [];

        if ($type === 'nomenclature' || $type === 'product') {
            $where[] = "`type` = ?";
            $binds[] = $type;
        }

        if ($status === 'active') {
            $where[] = "`is_active` = 1";
        } elseif ($status === 'archive') {
            $where[] = "`is_active` = 0";
        }

        $sql = "SELECT `id`, `parent_id`, `type`, `name`, `is_active` FROM `nomenclature_groups`";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY `name`";

        $stmt = fncQuery($sql, $binds);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $by_id = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $by_id[$row['id']] = $row;
        }

        $tree = [];
        foreach ($by_id as $id => $row) {
            if ($row['parent_id'] && isset($by_id[$row['parent_id']])) {
                $by_id[$row['parent_id']]['children'][] = &$by_id[$id];
            } else {
                $tree[] = &$by_id[$id];
            }
        }
        unset($row);

        $result = $tree;
        break;

    case 'group_info':
        if (!fncCan($perms, 'nomenclature.manage.view') && !fncCan($perms, 'products.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery("SELECT `id`, `parent_id`, `type`, `name`, `is_active` FROM `nomenclature_groups` WHERE `id` = ?", [$id]);
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    case 'new_group':
        if (!fncCan($perms, 'nomenclature.manage') && !fncCan($perms, 'products.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $name      = fncValFind('name', $params);
        $type      = fncValFind('type', $params);
        $parent_id = (int)fncValFind('parent_id', $params) ?: null;

        if (!$name || !in_array($type, ['nomenclature', 'product'], true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Укажите название']);
            exit;
        }

        if ($parent_id) {
            $stmt = fncQuery("SELECT `type` FROM `nomenclature_groups` WHERE `id` = ?", [$parent_id]);
            $parent = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if (!$parent || $parent['type'] !== $type) {
                echo json_encode(['sccss' => false, 'msg' => 'Некорректная родительская группа']);
                exit;
            }
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO `nomenclature_groups` (`parent_id`, `type`, `name`, `created_by`) VALUES (?, ?, ?, ?)",
            [$parent_id, $type, $name, $user_id]
        );
        $result = $stmt
            ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()]
            : ['sccss' => false, 'msg' => 'Не удалось создать группу'];
        break;

    case 'upd_group':
        if (!fncCan($perms, 'nomenclature.manage') && !fncCan($perms, 'products.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id        = (int)fncValFind('id', $params);
        $name      = fncValFind('name', $params);
        $parent_id = (int)fncValFind('parent_id', $params) ?: null;

        if (!$id || !$name) {
            echo json_encode(['sccss' => false, 'msg' => 'Укажите название']);
            exit;
        }

        $stmt = fncQuery("SELECT `type` FROM `nomenclature_groups` WHERE `id` = ?", [$id]);
        $current = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$current) {
            echo json_encode(['sccss' => false, 'msg' => 'Группа не найдена']);
            exit;
        }

        if ($parent_id) {
            $stmt = fncQuery("SELECT `id`, `parent_id`, `type` FROM `nomenclature_groups`");
            $all_rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $children_map = [];
            foreach ($all_rows as $row) {
                $children_map[$row['parent_id']][] = $row['id'];
            }

            $forbidden_ids = [$id];
            $queue = [$id];
            while ($queue) {
                $current_id = array_shift($queue);
                if (!empty($children_map[$current_id])) {
                    foreach ($children_map[$current_id] as $child_id) {
                        $forbidden_ids[] = $child_id;
                        $queue[] = $child_id;
                    }
                }
            }

            if (in_array($parent_id, $forbidden_ids, true)) {
                echo json_encode(['sccss' => false, 'msg' => 'Нельзя выбрать саму группу или её потомка родителем']);
                exit;
            }

            $stmt = fncQuery("SELECT `type` FROM `nomenclature_groups` WHERE `id` = ?", [$parent_id]);
            $parent = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if (!$parent || $parent['type'] !== $current['type']) {
                echo json_encode(['sccss' => false, 'msg' => 'Некорректная родительская группа']);
                exit;
            }
        }

        $stmt = fncQuery(
            "UPDATE `nomenclature_groups` SET `name` = ?, `parent_id` = ?, `updated_by` = ? WHERE `id` = ?",
            [$name, $parent_id, $user_id, $id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'archive_group':
        if (!fncCan($perms, 'nomenclature.manage') && !fncCan($perms, 'products.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)(fncValFind('id', $params) ?? ($_POST['id'] ?? 0));
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана группа']);
            exit;
        }

        $stmt = fncQuery("SELECT `id`, `parent_id`, `type` FROM `nomenclature_groups`");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $children_map = [];
        foreach ($rows as $row) {
            $children_map[$row['parent_id']][] = $row['id'];
        }

        $subtree_ids = [$id];
        $queue = [$id];
        while ($queue) {
            $current_id = array_shift($queue);
            if (!empty($children_map[$current_id])) {
                foreach ($children_map[$current_id] as $child_id) {
                    $subtree_ids[] = $child_id;
                    $queue[] = $child_id;
                }
            }
        }

        $placeholders = implode(',', array_fill(0, count($subtree_ids), '?'));
        $stmt = fncQuery(
            "SELECT COUNT(*) AS `cnt` FROM `nomenclature` WHERE `group_id` IN ($placeholders) AND `is_active` = 1",
            $subtree_ids
        );
        $active_count = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] : 0;

        if ($active_count > 0) {
            echo json_encode(['sccss' => false, 'msg' => 'Нельзя архивировать группу: в ней есть активные позиции (' . $active_count . ')']);
            exit;
        }

        $stmt = fncQuery(
            "UPDATE `nomenclature_groups` SET `is_active` = 0, `updated_by` = ? WHERE `id` IN ($placeholders)",
            array_merge([$user_id], $subtree_ids)
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'restore_group':
        if (!fncCan($perms, 'nomenclature.manage') && !fncCan($perms, 'products.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)(fncValFind('id', $params) ?? ($_POST['id'] ?? 0));
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана группа']);
            exit;
        }

        $stmt = fncQuery("SELECT `id`, `parent_id` FROM `nomenclature_groups`");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $by_id = [];
        foreach ($rows as $row) {
            $by_id[$row['id']] = $row['parent_id'];
        }

        if (!isset($by_id[$id])) {
            echo json_encode(['sccss' => false, 'msg' => 'Группа не найдена']);
            exit;
        }

        $ancestor_ids = [$id];
        $current_id = $id;
        while (!empty($by_id[$current_id])) {
            $current_id = $by_id[$current_id];
            $ancestor_ids[] = $current_id;
        }

        $placeholders = implode(',', array_fill(0, count($ancestor_ids), '?'));
        $stmt = fncQuery(
            "UPDATE `nomenclature_groups` SET `is_active` = 1, `updated_by` = ? WHERE `id` IN ($placeholders)",
            array_merge([$user_id], $ancestor_ids)
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
