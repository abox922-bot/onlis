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
            $where[] = "ng.type = :type";
            $binds['type'] = $type;
        }

        if ($status === 'active') {
            $where[] = "ng.is_active = 1";
        } elseif ($status === 'archive') {
            $where[] = "ng.is_active = 0";
        }

        $sql = "SELECT ng.id, ng.parent_id, ng.type, ng.name, ng.is_active
                FROM nomenclature_groups ng";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY ng.name";

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
        $stmt = fncQuery(
            "SELECT ng.id, ng.parent_id, ng.type, ng.name, ng.is_active
             FROM nomenclature_groups ng
             WHERE ng.id = :id",
            ['id' => $id]
        );
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
            $stmt = fncQuery("SELECT ng.type FROM nomenclature_groups ng WHERE ng.id = :id", ['id' => $parent_id]);
            $parent = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if (!$parent || $parent['type'] !== $type) {
                echo json_encode(['sccss' => false, 'msg' => 'Некорректная родительская группа']);
                exit;
            }
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO nomenclature_groups (parent_id, type, name, created_by) VALUES (:parent_id, :type, :name, :created_by)",
            ['parent_id' => $parent_id, 'type' => $type, 'name' => $name, 'created_by' => $user_id]
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

        $stmt = fncQuery("SELECT ng.type FROM nomenclature_groups ng WHERE ng.id = :id", ['id' => $id]);
        $current = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$current) {
            echo json_encode(['sccss' => false, 'msg' => 'Группа не найдена']);
            exit;
        }

        if ($parent_id) {
            $stmt = fncQuery("SELECT ng.id, ng.parent_id, ng.type FROM nomenclature_groups ng");
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

            $stmt = fncQuery("SELECT ng.type FROM nomenclature_groups ng WHERE ng.id = :id", ['id' => $parent_id]);
            $parent = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if (!$parent || $parent['type'] !== $current['type']) {
                echo json_encode(['sccss' => false, 'msg' => 'Некорректная родительская группа']);
                exit;
            }
        }

        $stmt = fncQuery(
            "UPDATE nomenclature_groups SET name = :name, parent_id = :parent_id, updated_by = :updated_by WHERE id = :id",
            ['name' => $name, 'parent_id' => $parent_id, 'updated_by' => $user_id, 'id' => $id]
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

        $stmt = fncQuery("SELECT ng.id, ng.parent_id, ng.type FROM nomenclature_groups ng");
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

        $in_placeholders = [];
        $in_binds = [];
        foreach ($subtree_ids as $i => $sid) {
            $key = "sid{$i}";
            $in_placeholders[] = ":{$key}";
            $in_binds[$key] = $sid;
        }
        $in_sql = implode(',', $in_placeholders);

        $stmt = fncQuery(
            "SELECT COUNT(*) AS cnt FROM nomenclature n WHERE n.group_id IN ($in_sql) AND n.is_active = 1",
            $in_binds
        );
        $active_count = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] : 0;

        if ($active_count > 0) {
            echo json_encode(['sccss' => false, 'msg' => 'Нельзя архивировать группу: в ней есть активные позиции (' . $active_count . ')']);
            exit;
        }

        $stmt = fncQuery(
            "UPDATE nomenclature_groups SET is_active = 0, updated_by = :updated_by WHERE id IN ($in_sql)",
            array_merge(['updated_by' => $user_id], $in_binds)
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

        $stmt = fncQuery("SELECT ng.id, ng.parent_id FROM nomenclature_groups ng");
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

        $in_placeholders = [];
        $in_binds = [];
        foreach ($ancestor_ids as $i => $aid) {
            $key = "aid{$i}";
            $in_placeholders[] = ":{$key}";
            $in_binds[$key] = $aid;
        }
        $in_sql = implode(',', $in_placeholders);

        $stmt = fncQuery(
            "UPDATE nomenclature_groups SET is_active = 1, updated_by = :updated_by WHERE id IN ($in_sql)",
            array_merge(['updated_by' => $user_id], $in_binds)
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'nomenclature_list':
        if (!fncCan($perms, 'nomenclature.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $section  = fncValFind('section', $params) ?: ($_POST['section'] ?? '');
        $status   = fncValFind('status', $params) ?: ($_POST['status'] ?? 'active');
        $group_id = (int)(fncValFind('group_id', $params) ?: ($_POST['group_id'] ?? 0));

        $where = [];
        $binds = [];

        if ($section === 'produced') {
            $where[] = "n.is_produced = 1 AND n.is_sellable = 0";
        } else {
            $where[] = "n.is_purchased = 1";
        }

        if ($status === 'active') {
            $where[] = "n.is_active = 1";
        }

        if ($group_id) {
            $stmt = fncQuery("SELECT ng.id, ng.parent_id FROM nomenclature_groups ng");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $children_map = [];
            foreach ($rows as $row) {
                $children_map[$row['parent_id']][] = $row['id'];
            }

            $group_ids = [$group_id];
            $queue = [$group_id];
            while ($queue) {
                $current_id = array_shift($queue);
                if (!empty($children_map[$current_id])) {
                    foreach ($children_map[$current_id] as $child_id) {
                        $group_ids[] = $child_id;
                        $queue[] = $child_id;
                    }
                }
            }

            $in_placeholders = [];
            foreach ($group_ids as $i => $gid) {
                $key = "gid{$i}";
                $in_placeholders[] = ":{$key}";
                $binds[$key] = $gid;
            }
            $where[] = "n.group_id IN (" . implode(',', $in_placeholders) . ")";
        }

        if ($section === 'produced') {
            $sql = "SELECT n.id, n.name, n.is_active,
                           (SELECT COUNT(*) FROM nomenclature_composition nc WHERE nc.nomenclature_id = n.id) AS ingredients_count
                    FROM nomenclature n
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY n.name";
        } else {
            $sql = "SELECT n.id, n.name, n.is_active, ng.name AS group_name
                    FROM nomenclature n
                    LEFT JOIN nomenclature_groups ng ON ng.id = n.group_id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY n.name";
        }

        $stmt = fncQuery($sql, $binds);
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    case 'new_nomenclature':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $section = fncValFind('section', $params);
        if (!in_array($section, ['purchased', 'produced'], true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Некорректный раздел']);
            exit;
        }

        $name                 = fncValFind('name', $params);
        $display_name         = fncValFind('display_name', $params) ?: null;
        $description          = fncValFind('description', $params) ?: null;
        $unit_id              = (int)fncValFind('unit_id', $params);
        $default_supplier_id  = (int)fncValFind('default_supplier_id', $params) ?: null;
        $is_sellable          = $section === 'purchased' ? (int)fncValFind('is_sellable', $params) : 0;

        if ($section === 'purchased') {
            $is_food_product_raw = fncValFind('is_food_product', $params);
            if ($is_food_product_raw === null || $is_food_product_raw === '') {
                echo json_encode(['sccss' => false, 'msg' => 'Укажите, является ли позиция пищевой продукцией']);
                exit;
            }
            $is_food_product = (int)$is_food_product_raw;
        } else {
            $is_food_product = 1;
        }

        if (!$name || !$unit_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        if ($section === 'produced') {
            $stmt = fncQuery("SELECT ng.id FROM nomenclature_groups ng WHERE ng.type = 'semi_finished' LIMIT 1");
            $sf_group = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if (!$sf_group) {
                echo json_encode(['sccss' => false, 'msg' => 'Не найдена системная группа полуфабрикатов']);
                exit;
            }
            $group_id = (int)$sf_group['id'];
        } else {
            $group_id = (int)fncValFind('group_id', $params);
            if (!$group_id) {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
                exit;
            }
            $stmt = fncQuery("SELECT ng.type FROM nomenclature_groups ng WHERE ng.id = :id", ['id' => $group_id]);
            $group = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if (!$group || $group['type'] !== 'nomenclature') {
                echo json_encode(['sccss' => false, 'msg' => 'Некорректная группа']);
                exit;
            }
        }

        $is_purchased = $section === 'purchased' ? 1 : 0;
        $is_produced  = $section === 'produced' ? 1 : 0;

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO nomenclature
                (group_id, name, display_name, description, unit_id,
                 is_purchased, is_produced, is_sellable, is_food_product, default_supplier_id, created_by)
             VALUES
                (:group_id, :name, :display_name, :description, :unit_id,
                 :is_purchased, :is_produced, :is_sellable, :is_food_product, :default_supplier_id, :created_by)",
            [
                'group_id'             => $group_id,
                'name'                 => $name,
                'display_name'         => $display_name,
                'description'          => $description,
                'unit_id'              => $unit_id,
                'is_purchased'         => $is_purchased,
                'is_produced'          => $is_produced,
                'is_sellable'          => $is_sellable,
                'is_food_product'      => $is_food_product,
                'default_supplier_id'  => $default_supplier_id,
                'created_by'           => $user_id,
            ]
        );
        $result = $stmt
            ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()]
            : ['sccss' => false, 'msg' => 'Не удалось создать позицию'];
        break;

    case 'nomenclature_info':
        if (!fncCan($perms, 'nomenclature.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT n.id, n.group_id, n.name, n.display_name, n.description, n.unit_id,
                    n.is_purchased, n.is_produced, n.is_sellable, n.is_food_product, n.default_supplier_id, n.is_active,
                    u.short_name AS unit_short_name,
                    (SELECT COUNT(*) FROM nomenclature_nutrition nn WHERE nn.nomenclature_id = n.id) AS has_nutrition_data
             FROM nomenclature n
             LEFT JOIN units u ON u.id = n.unit_id
             WHERE n.id = :id",
            ['id' => $id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    case 'upd_nomenclature':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)fncValFind('id', $params);
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана позиция']);
            exit;
        }

        $stmt = fncQuery("SELECT n.is_produced, n.group_id, n.is_food_product FROM nomenclature n WHERE n.id = :id", ['id' => $id]);
        $current = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$current) {
            echo json_encode(['sccss' => false, 'msg' => 'Позиция не найдена']);
            exit;
        }

        $name                 = fncValFind('name', $params);
        $display_name         = fncValFind('display_name', $params) ?: null;
        $description          = fncValFind('description', $params) ?: null;
        $unit_id              = (int)fncValFind('unit_id', $params);
        $default_supplier_id  = (int)fncValFind('default_supplier_id', $params) ?: null;
        $is_sellable          = $current['is_produced'] ? 0 : (int)fncValFind('is_sellable', $params);

        $is_food_product_raw = fncValFind('is_food_product', $params);
        if ($is_food_product_raw === null || $is_food_product_raw === '') {
            echo json_encode(['sccss' => false, 'msg' => 'Укажите, является ли позиция пищевой продукцией']);
            exit;
        }
        $is_food_product = (int)$is_food_product_raw;

        if ((int)$current['is_food_product'] === 1 && $is_food_product === 0) {
            $stmt = fncQuery(
                "SELECT COUNT(*) AS cnt FROM nomenclature_nutrition nn WHERE nn.nomenclature_id = :id",
                ['id' => $id]
            );
            $has_nutrition = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0 : false;
            if ($has_nutrition) {
                echo json_encode(['sccss' => false, 'msg' => 'Сначала очистите данные КБЖУ на соответствующей вкладке']);
                exit;
            }
        }

        if (!$name || !$unit_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        if ($current['is_produced']) {
            $group_id = (int)$current['group_id'];
        } else {
            $group_id = (int)fncValFind('group_id', $params);
            if (!$group_id) {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
                exit;
            }
            $stmt = fncQuery("SELECT ng.type FROM nomenclature_groups ng WHERE ng.id = :id", ['id' => $group_id]);
            $group = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            if (!$group || $group['type'] !== 'nomenclature') {
                echo json_encode(['sccss' => false, 'msg' => 'Некорректная группа']);
                exit;
            }
        }

        $stmt = fncQuery(
            "UPDATE nomenclature
             SET group_id = :group_id, name = :name, display_name = :display_name, description = :description,
                 unit_id = :unit_id, is_sellable = :is_sellable, is_food_product = :is_food_product,
                 default_supplier_id = :default_supplier_id, updated_by = :updated_by
             WHERE id = :id",
            [
                'group_id'             => $group_id,
                'name'                 => $name,
                'display_name'         => $display_name,
                'description'          => $description,
                'unit_id'              => $unit_id,
                'is_sellable'          => $is_sellable,
                'is_food_product'      => $is_food_product,
                'default_supplier_id'  => $default_supplier_id,
                'updated_by'           => $user_id,
                'id'                   => $id,
            ]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'archive_nomenclature':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)(fncValFind('id', $params) ?? ($_POST['id'] ?? 0));
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана позиция']);
            exit;
        }
        $stmt = fncQuery(
            "UPDATE nomenclature SET is_active = 0, updated_by = :updated_by WHERE id = :id",
            ['updated_by' => $user_id, 'id' => $id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'restore_nomenclature':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)(fncValFind('id', $params) ?? ($_POST['id'] ?? 0));
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана позиция']);
            exit;
        }
        $stmt = fncQuery(
            "UPDATE nomenclature SET is_active = 1, updated_by = :updated_by WHERE id = :id",
            ['updated_by' => $user_id, 'id' => $id]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'package_list':
        if (!fncCan($perms, 'nomenclature.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT np.id, np.name, np.quantity, np.is_default, u.short_name AS unit_short_name
             FROM nomenclature_packages np
             LEFT JOIN nomenclature n ON n.id = np.nomenclature_id
             LEFT JOIN units u ON u.id = n.unit_id
             WHERE np.nomenclature_id = :nomenclature_id
             ORDER BY np.is_default DESC, np.name",
            ['nomenclature_id' => $nomenclature_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    case 'package_info':
        if (!fncCan($perms, 'nomenclature.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $stmt = fncQuery(
            "SELECT np.id, np.nomenclature_id, np.name, np.quantity, np.is_default, u.short_name AS unit_short_name
             FROM nomenclature_packages np
             LEFT JOIN nomenclature n ON n.id = np.nomenclature_id
             LEFT JOIN units u ON u.id = n.unit_id
             WHERE np.id = :id",
            ['id' => $id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    case 'new_package':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)fncValFind('nomenclature_id', $params);
        $name            = fncValFind('name', $params);
        $quantity        = fncValFind('quantity', $params);
        $is_default      = (int)fncValFind('is_default', $params);

        if (!$nomenclature_id || !$name || !$quantity) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT COUNT(*) AS cnt FROM nomenclature_packages np WHERE np.nomenclature_id = :nomenclature_id",
            ['nomenclature_id' => $nomenclature_id]
        );
        $existing_count = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] : 0;

        if ($existing_count === 0) {
            $is_default = 1;
        }

        if ($is_default) {
            fncQuery(
                "UPDATE nomenclature_packages SET is_default = 0 WHERE nomenclature_id = :nomenclature_id",
                ['nomenclature_id' => $nomenclature_id]
            );
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO nomenclature_packages (nomenclature_id, name, quantity, is_default, created_by)
             VALUES (:nomenclature_id, :name, :quantity, :is_default, :created_by)",
            [
                'nomenclature_id' => $nomenclature_id,
                'name'            => $name,
                'quantity'        => $quantity,
                'is_default'      => $is_default,
                'created_by'      => $user_id,
            ]
        );
        $result = $stmt
            ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()]
            : ['sccss' => false, 'msg' => 'Не удалось создать упаковку'];
        break;

    case 'upd_package':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id         = (int)fncValFind('id', $params);
        $name       = fncValFind('name', $params);
        $quantity   = fncValFind('quantity', $params);
        $is_default = (int)fncValFind('is_default', $params);

        if (!$id || !$name || !$quantity) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT np.nomenclature_id, np.is_default FROM nomenclature_packages np WHERE np.id = :id",
            ['id' => $id]
        );
        $current = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$current) {
            echo json_encode(['sccss' => false, 'msg' => 'Упаковка не найдена']);
            exit;
        }

        if ($current['is_default'] && !$is_default) {
            echo json_encode(['sccss' => false, 'msg' => 'Должна остаться хотя бы одна упаковка по умолчанию — назначьте другую']);
            exit;
        }

        if ($is_default) {
            fncQuery(
                "UPDATE nomenclature_packages SET is_default = 0 WHERE nomenclature_id = :nomenclature_id AND id != :id",
                ['nomenclature_id' => $current['nomenclature_id'], 'id' => $id]
            );
        }

        $stmt = fncQuery(
            "UPDATE nomenclature_packages
             SET name = :name, quantity = :quantity, is_default = :is_default, updated_by = :updated_by
             WHERE id = :id",
            [
                'name'        => $name,
                'quantity'    => $quantity,
                'is_default'  => $is_default,
                'updated_by'  => $user_id,
                'id'          => $id,
            ]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'nutrition_info':
        if (!fncCan($perms, 'nomenclature.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT nn.calories, nn.proteins, nn.fats, nn.carbohydrates, n.is_produced, n.is_food_product, u.is_float AS unit_is_float
             FROM nomenclature n
             LEFT JOIN nomenclature_nutrition nn ON nn.nomenclature_id = n.id
             LEFT JOIN units u ON u.id = n.unit_id
             WHERE n.id = :nomenclature_id",
            ['nomenclature_id' => $nomenclature_id]
        );
        $result = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        break;

    case 'upd_nutrition':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)fncValFind('nomenclature_id', $params);
        $calories        = fncValFind('calories', $params);
        $proteins        = fncValFind('proteins', $params);
        $fats            = fncValFind('fats', $params);
        $carbohydrates   = fncValFind('carbohydrates', $params);

        if (!$nomenclature_id || $calories === null || $proteins === null || $fats === null || $carbohydrates === null) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните все поля']);
            exit;
        }

        $stmt = fncQuery("SELECT n.is_produced, n.is_food_product FROM nomenclature n WHERE n.id = :id", ['id' => $nomenclature_id]);
        $nom_check = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if ($nom_check && $nom_check['is_produced']) {
            echo json_encode(['sccss' => false, 'msg' => 'КБЖУ полуфабриката рассчитывается автоматически и не редактируется вручную']);
            exit;
        }
        if ($nom_check && !$nom_check['is_food_product']) {
            echo json_encode(['sccss' => false, 'msg' => 'Позиция не является пищевой продукцией']);
            exit;
        }

        $stmt = fncQuery(
            "INSERT INTO nomenclature_nutrition (nomenclature_id, calories, proteins, fats, carbohydrates, created_by)
             VALUES (:nomenclature_id, :calories, :proteins, :fats, :carbohydrates, :created_by)
             ON DUPLICATE KEY UPDATE
                calories = :calories2, proteins = :proteins2, fats = :fats2, carbohydrates = :carbohydrates2,
                updated_by = :updated_by",
            [
                'nomenclature_id' => $nomenclature_id,
                'calories'        => $calories,
                'proteins'        => $proteins,
                'fats'            => $fats,
                'carbohydrates'   => $carbohydrates,
                'created_by'      => $user_id,
                'calories2'       => $calories,
                'proteins2'       => $proteins,
                'fats2'           => $fats,
                'carbohydrates2'  => $carbohydrates,
                'updated_by'      => $user_id,
            ]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'clear_nutrition':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)(fncValFind('nomenclature_id', $params) ?? ($_POST['nomenclature_id'] ?? 0));
        if (!$nomenclature_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана позиция']);
            exit;
        }
        $stmt = fncQuery("DELETE FROM nomenclature_nutrition WHERE nomenclature_id = :nomenclature_id", ['nomenclature_id' => $nomenclature_id]);
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'affiliation_info':
        if (!fncCan($perms, 'nomenclature.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);

        $stmt = fncQuery("SELECT ot.id, ot.name FROM object_types ot WHERE ot.is_operational = 1 AND ot.is_active = 1 ORDER BY ot.name");
        $types = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmt = fncQuery(
            "SELECT otw.type_id, otw.workstation_id, w.name AS workstation_name
             FROM object_type_workstations otw
             LEFT JOIN workstations w ON w.id = otw.workstation_id
             ORDER BY w.name"
        );
        $ws_rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $workstations_by_type = [];
        foreach ($ws_rows as $row) {
            $workstations_by_type[$row['type_id']][] = ['id' => $row['workstation_id'], 'name' => $row['workstation_name']];
        }

        $stmt = fncQuery(
            "SELECT nw.object_type_id, nw.workstation_id FROM nomenclature_workstations nw WHERE nw.nomenclature_id = :nomenclature_id",
            ['nomenclature_id' => $nomenclature_id]
        );
        $current_rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $current_by_type = [];
        foreach ($current_rows as $row) {
            $current_by_type[$row['object_type_id']] = $row['workstation_id'];
        }

        foreach ($types as &$type) {
            $type['workstations']         = $workstations_by_type[$type['id']] ?? [];
            $type['selected_workstation'] = $current_by_type[$type['id']] ?? 0;
        }
        unset($type);

        $result = $types;
        break;

    case 'upd_nomenclature_workstation':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)fncValFind('nomenclature_id', $params);
        $object_type_id  = (int)fncValFind('object_type_id', $params);
        $workstation_id  = (int)fncValFind('workstation_id', $params);

        if (!$nomenclature_id || !$object_type_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Некорректные параметры']);
            exit;
        }

        if (!$workstation_id) {
            $stmt = fncQuery(
                "DELETE FROM nomenclature_workstations WHERE nomenclature_id = :nomenclature_id AND object_type_id = :object_type_id",
                ['nomenclature_id' => $nomenclature_id, 'object_type_id' => $object_type_id]
            );
            $result = ['sccss' => (bool)$stmt];
            break;
        }

        $stmt = fncQuery(
            "SELECT COUNT(*) AS cnt FROM object_type_workstations otw
             WHERE otw.type_id = :object_type_id AND otw.workstation_id = :workstation_id",
            ['object_type_id' => $object_type_id, 'workstation_id' => $workstation_id]
        );
        $is_valid_pair = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0 : false;
        if (!$is_valid_pair) {
            echo json_encode(['sccss' => false, 'msg' => 'Указанная станция недоступна для этого типа объекта']);
            exit;
        }

        $stmt = fncQuery(
            "INSERT INTO nomenclature_workstations (nomenclature_id, object_type_id, workstation_id, created_by)
             VALUES (:nomenclature_id, :object_type_id, :workstation_id, :created_by)
             ON DUPLICATE KEY UPDATE workstation_id = :workstation_id2, updated_by = :updated_by",
            [
                'nomenclature_id' => $nomenclature_id,
                'object_type_id'  => $object_type_id,
                'workstation_id'  => $workstation_id,
                'created_by'      => $user_id,
                'workstation_id2' => $workstation_id,
                'updated_by'      => $user_id,
            ]
        );
        $result = ['sccss' => (bool)$stmt];
        break;

    case 'composition_list':
        if (!fncCan($perms, 'nomenclature.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT nc.id, n.id AS ingredient_id, n.name, n.is_purchased, n.is_produced
             FROM nomenclature_composition nc
             LEFT JOIN nomenclature n ON n.id = nc.ingredient_id
             WHERE nc.nomenclature_id = :nomenclature_id
             ORDER BY n.name",
            ['nomenclature_id' => $nomenclature_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    case 'composition_available':
        if (!fncCan($perms, 'nomenclature.manage.view')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);
        $stmt = fncQuery(
            "SELECT n.id, n.name
             FROM nomenclature n
             WHERE (n.is_purchased = 1 OR n.is_produced = 1)
               AND n.is_active = 1
               AND n.id != :nomenclature_id
               AND n.id NOT IN (
                   SELECT nc.ingredient_id FROM nomenclature_composition nc WHERE nc.nomenclature_id = :nomenclature_id2
               )
             ORDER BY n.name",
            ['nomenclature_id' => $nomenclature_id, 'nomenclature_id2' => $nomenclature_id]
        );
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        break;

    case 'new_composition_item':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $nomenclature_id = (int)fncValFind('nomenclature_id', $params);
        $ingredient_id    = (int)fncValFind('ingredient_id', $params);

        if (!$nomenclature_id || !$ingredient_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Выберите ингредиент']);
            exit;
        }
        if ($nomenclature_id === $ingredient_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Нельзя добавить позицию в свой же состав']);
            exit;
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO nomenclature_composition (nomenclature_id, ingredient_id, created_by)
             VALUES (:nomenclature_id, :ingredient_id, :created_by)",
            ['nomenclature_id' => $nomenclature_id, 'ingredient_id' => $ingredient_id, 'created_by' => $user_id]
        );
        $result = $stmt
            ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()]
            : ['sccss' => false, 'msg' => 'Не удалось добавить ингредиент'];
        break;

    case 'del_composition_item':
        if (!fncCan($perms, 'nomenclature.manage')) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $id = (int)(fncValFind('id', $params) ?? ($_POST['id'] ?? 0));
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан ингредиент']);
            exit;
        }
        $stmt = fncQuery("DELETE FROM nomenclature_composition WHERE id = :id", ['id' => $id]);
        $result = ['sccss' => (bool)$stmt];
        break;

    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
