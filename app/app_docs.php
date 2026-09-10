<?php
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

function fncOrgDisplayName($name, $abbreviation, $is_individual) {
    if (!$name) return null;
    return $is_individual
        ? $abbreviation . ' ' . $name
        : $abbreviation . ' «' . $name . '»';
}

switch ($action) {

    //==========================================================================
    // Объединяющий action для toolbar раздела Документы: типы документов
    // (для фильтра и решения о кнопке "Добавить") + склады (для фильтра).
    case 'documents_toolbar_data':
        $stmt = fncQuery(
            "SELECT dt.code, dt.name
             FROM document_types dt
             WHERE dt.is_active = 1
             ORDER BY dt.name"
        );
        $document_types = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmt = fncQuery(
            "SELECT o.id, o.name
             FROM objects o
             WHERE o.is_stock = 1 AND o.is_active = 1
             ORDER BY o.name"
        );
        $warehouses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $result = [
            'document_types' => $document_types,
            'warehouses'     => $warehouses,
        ];
        break;

    //==========================================================================
    // Объединяющий action для формы создания документа: типы документов
    // (отфильтрованы по правам конкретного пользователя), организации и
    // склады (все — для полного доступа inventory, иначе только те, где
    // пользователь сейчас числится сотрудником, через organization_staff),
    // контрагенты (с ОПФ для отображения).
    case 'document_new_form_data':
        $stmt = fncQuery(
            "SELECT dt.code, dt.name, dt.direction, dt.warehouse_count, dt.warehouse_field, dt.numbering_mode
             FROM document_types dt
             WHERE dt.is_active = 1 AND dt.is_manual = 1
             ORDER BY dt.name"
        );
        $all_document_types = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $document_types = [];
        foreach ($all_document_types as $dt) {
            if (fncCan($perms, 'inventory.documents.' . $dt['code'])) {
                $document_types[] = $dt;
            }
        }

        $has_full_access = fncCan($perms, 'inventory');

        if ($has_full_access) {
            $stmt = fncQuery(
                "SELECT o.id, o.name
                 FROM organizations o
                 WHERE o.is_contractor = 0 AND o.is_active = 1
                   AND EXISTS (
                       SELECT 1 FROM objects ob
                       WHERE ob.organization_id = o.id AND ob.is_stock = 1 AND ob.is_active = 1
                   )
                 ORDER BY o.name"
            );
        } else {
            $stmt = fncQuery(
                "SELECT DISTINCT o.id, o.name
                 FROM organizations o
                 INNER JOIN organization_staff os ON os.organization_id = o.id
                 WHERE o.is_contractor = 0 AND o.is_active = 1
                   AND os.user_id = :user_id AND os.date_end IS NULL
                   AND EXISTS (
                       SELECT 1 FROM objects ob
                       WHERE ob.organization_id = o.id AND ob.is_stock = 1 AND ob.is_active = 1
                   )
                 ORDER BY o.name",
                ['user_id' => $user_id]
            );
        }
        $organizations = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        if ($has_full_access) {
            $stmt = fncQuery(
                "SELECT ob.id, ob.name, ob.organization_id
                 FROM objects ob
                 WHERE ob.is_stock = 1 AND ob.is_active = 1
                 ORDER BY ob.name"
            );
        } else {
            $stmt = fncQuery(
                "SELECT DISTINCT ob.id, ob.name, ob.organization_id
                 FROM objects ob
                 INNER JOIN organization_staff os ON os.organization_id = ob.organization_id
                 WHERE ob.is_stock = 1 AND ob.is_active = 1
                   AND os.user_id = :user_id AND os.date_end IS NULL
                 ORDER BY ob.name",
                ['user_id' => $user_id]
            );
        }
        $warehouses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmt = fncQuery(
            "SELECT o.id, o.name, ot.abbreviation, ot.is_individual
             FROM organizations o
             LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
             WHERE o.is_contractor = 1 AND o.is_active = 1
             ORDER BY o.name"
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $counterparties = [];
        foreach ($rows as $row) {
            $counterparties[] = [
                'id' => $row['id'],
                'display_name' => fncOrgDisplayName($row['name'], $row['abbreviation'], $row['is_individual']),
            ];
        }

        $result = [
            'document_types' => $document_types,
            'organizations'  => $organizations,
            'warehouses'     => $warehouses,
            'counterparties' => $counterparties,
        ];
        break;

    //==========================================================================
    // Создание черновика документа. Все проверки (принадлежность RBAC, наличие
    // незавершённого документа по складу) и сам INSERT — в одной транзакции.
    // Блокировка строк objects для задействованных складов через FOR UPDATE
    // защищает от гонки: параллельный запрос на тот же склад будет ждать
    // завершения этой транзакции, а не пройдёт проверку "чисто" одновременно.
    // outstanding_sum значим только для incoming (там есть что гасить платежами
    // по конкретному документу) — для internal/outgoing всегда NULL.
    case 'new_document':
        $type_code = fncValFind('type_code', $params);

        if (!$type_code || !fncCan($perms, 'inventory.documents.' . $type_code)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT id, direction, warehouse_count, warehouse_field, numbering_mode
             FROM document_types
             WHERE code = :code AND is_active = 1 AND is_manual = 1",
            ['code' => $type_code]
        );
        $doc_type = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$doc_type) {
            echo json_encode(['sccss' => false, 'msg' => 'Некорректный тип документа']);
            exit;
        }

        $organization_id = (int)fncValFind('organization_id', $params);
        if (!$organization_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Выберите организацию']);
            exit;
        }

        $warehouse_out_id = null;
        $warehouse_in_id  = null;

        if ((int)$doc_type['warehouse_count'] === 2) {
            $warehouse_out_id = (int)fncValFind('warehouse_out_id', $params) ?: null;
            $warehouse_in_id  = (int)fncValFind('warehouse_in_id', $params) ?: null;
            if (!$warehouse_out_id || !$warehouse_in_id) {
                echo json_encode(['sccss' => false, 'msg' => 'Выберите оба склада']);
                exit;
            }
            if ($warehouse_out_id === $warehouse_in_id) {
                echo json_encode(['sccss' => false, 'msg' => 'Склады должны отличаться']);
                exit;
            }
        } elseif ($doc_type['warehouse_field'] === 'out') {
            $warehouse_out_id = (int)fncValFind('warehouse_out_id', $params) ?: null;
            if (!$warehouse_out_id) {
                echo json_encode(['sccss' => false, 'msg' => 'Выберите склад списания']);
                exit;
            }
        } elseif ($doc_type['warehouse_field'] === 'in') {
            $warehouse_in_id = (int)fncValFind('warehouse_in_id', $params) ?: null;
            if (!$warehouse_in_id) {
                echo json_encode(['sccss' => false, 'msg' => 'Выберите склад поступления']);
                exit;
            }
            if ($type_code === 'sf_production') {
                $warehouse_out_id = $warehouse_in_id;
            }
        }

        $counterparty_id = null;
        if (in_array($doc_type['direction'], ['incoming', 'outgoing'], true)) {
            $counterparty_id = (int)fncValFind('counterparty_id', $params) ?: null;
            if (!$counterparty_id) {
                echo json_encode(['sccss' => false, 'msg' => 'Выберите контрагента']);
                exit;
            }
        }

        $number = null;
        $document_date = date('Y-m-d');
        $sum = 0;
        $tax_sum = 0;
        $outstanding_sum = null;

        if ($doc_type['direction'] === 'incoming') {
            $number = fncValFind('number', $params);
            $document_date = fncValFind('document_date', $params);
            $sum = fncValFind('sum', $params);
            $tax_sum = fncValFind('tax_sum', $params);

            if (!$number || !$document_date || $sum === null || $sum === '') {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните все обязательные поля']);
                exit;
            }
            $outstanding_sum = $sum;
        }

        global $pdo;

        try {
            $pdo->beginTransaction();

            $warehouse_ids = array_unique(array_filter([$warehouse_out_id, $warehouse_in_id]));

            foreach ($warehouse_ids as $wid) {
                fncQuery("SELECT id FROM objects WHERE id = :id FOR UPDATE", ['id' => $wid]);
            }

            if (!empty($warehouse_ids)) {
                $out_placeholders = [];
                $in_placeholders  = [];
                $binds = [];

                foreach (array_values($warehouse_ids) as $i => $wid) {
                    $out_key = "out_wid{$i}";
                    $in_key  = "in_wid{$i}";
                    $out_placeholders[] = ":{$out_key}";
                    $in_placeholders[]  = ":{$in_key}";
                    $binds[$out_key] = $wid;
                    $binds[$in_key]  = $wid;
                }

                $out_sql = implode(',', $out_placeholders);
                $in_sql  = implode(',', $in_placeholders);

                $stmt = fncQuery(
                    "SELECT id FROM documents
                     WHERE status IN ('draft','posting')
                       AND (warehouse_out_id IN ($out_sql) OR warehouse_in_id IN ($in_sql))
                     LIMIT 1",
                    $binds
                );
                $blocking = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
                if ($blocking) {
                    $pdo->rollBack();
                    echo json_encode(['sccss' => false, 'msg' => 'По одному из выбранных складов уже есть незавершённый документ']);
                    exit;
                }
            }

            if ($doc_type['numbering_mode'] === 'auto') {
                $stmt = fncQuery(
                    "SELECT MAX(CAST(number AS UNSIGNED)) AS max_number
                     FROM documents
                     WHERE document_type_id = :type_id
                     FOR UPDATE",
                    ['type_id' => $doc_type['id']]
                );
                $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
                $number = (string)(((int)($row['max_number'] ?? 0)) + 1);
            }

            $stmt = fncQuery(
                "INSERT INTO documents
                    (document_type_id, organization_id, number, document_date, status,
                     warehouse_out_id, warehouse_in_id, counterparty_id, sum, tax_sum,
                     outstanding_sum, created_by)
                 VALUES
                    (:document_type_id, :organization_id, :number, :document_date, 'draft',
                     :warehouse_out_id, :warehouse_in_id, :counterparty_id, :sum, :tax_sum,
                     :outstanding_sum, :created_by)",
                [
                    'document_type_id' => $doc_type['id'],
                    'organization_id'  => $organization_id,
                    'number'           => $number,
                    'document_date'    => $document_date,
                    'warehouse_out_id' => $warehouse_out_id,
                    'warehouse_in_id'  => $warehouse_in_id,
                    'counterparty_id'  => $counterparty_id,
                    'sum'              => $sum,
                    'tax_sum'          => $tax_sum,
                    'outstanding_sum'  => $outstanding_sum,
                    'created_by'       => $user_id,
                ]
            );

            if (!$stmt) {
                $pdo->rollBack();
                echo json_encode(['sccss' => false, 'msg' => 'Не удалось создать документ']);
                exit;
            }

            $new_id = (int)$pdo->lastInsertId();
            $pdo->commit();

            $result = ['sccss' => true, 'id' => $new_id];

        } catch (Exception $e) {
            $pdo->rollBack();
            fncLog('Ошибка создания документа: ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Произошла ошибка при создании документа']);
            exit;
        }
        break;

    //==========================================================================
    // Список документов за период с фильтрами по типу и складу. Сортировка:
    // сначала по document_date (новые сверху), внутри дня — draft/posting
    // перед posted, внутри статуса — по created_at (новые сверху). Права на
    // просмотр пока не фильтруются построчно — раздел уже защищён per-type
    // правами на уровне видимости самого модуля (см. slug_prefix).
    case 'documents_list':
        $date_from    = $_POST['date_from'] ?? '';
        $date_to      = $_POST['date_to'] ?? '';
        $type_code    = $_POST['type_code'] ?? '';
        $warehouse_id = (int)($_POST['warehouse_id'] ?? 0);

        if (!$date_from || !$date_to) {
            $result = [];
            break;
        }

        $where = ["d.document_date BETWEEN :date_from AND :date_to"];
        $binds = ['date_from' => $date_from, 'date_to' => $date_to];

        if ($type_code) {
            $where[] = "dt.code = :type_code";
            $binds['type_code'] = $type_code;
        }

        if ($warehouse_id) {
            $where[] = "(d.warehouse_out_id = :warehouse_id OR d.warehouse_in_id = :warehouse_id2)";
            $binds['warehouse_id']  = $warehouse_id;
            $binds['warehouse_id2'] = $warehouse_id;
        }

        $sql = "SELECT d.id, d.number, d.document_date, d.status, d.sum,
                       dt.code, dt.name AS type_name,
                       o.name AS organization_name, oot.abbreviation AS organization_abbreviation, oot.is_individual AS organization_is_individual,
                       c.name AS counterparty_name, cot.abbreviation AS counterparty_abbreviation, cot.is_individual AS counterparty_is_individual,
                       wo.name AS warehouse_out_name,
                       wi.name AS warehouse_in_name
                FROM documents d
                LEFT JOIN document_types dt ON dt.id = d.document_type_id
                LEFT JOIN organizations o ON o.id = d.organization_id
                LEFT JOIN organization_types oot ON oot.id = o.organization_type_id
                LEFT JOIN organizations c ON c.id = d.counterparty_id
                LEFT JOIN organization_types cot ON cot.id = c.organization_type_id
                LEFT JOIN objects wo ON wo.id = d.warehouse_out_id
                LEFT JOIN objects wi ON wi.id = d.warehouse_in_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY d.document_date DESC,
                         (d.status = 'posted') ASC,
                         d.created_at DESC";

        $stmt = fncQuery($sql, $binds);
        $result = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($result as $key => $row) {
            $result[$key]['organization_display_name'] = fncOrgDisplayName($row['organization_name'], $row['organization_abbreviation'], $row['organization_is_individual']);
            $result[$key]['counterparty_display_name']  = fncOrgDisplayName($row['counterparty_name'], $row['counterparty_abbreviation'], $row['counterparty_is_individual']);
        }
        break;

    //==========================================================================
    // Карточка документа: шапка + строки + справочники для редактируемых полей
    // шапки (только если документ ещё пуст — организация/склад/контрагент
    // заморожены после первой строки, см. правило проекта). Номенклатура
    // грузится только для draft-документов (не нужна для posted/posting).
    // Для incoming дополнительно — последняя цена этой номенклатуры по этой
    // организации (из document_items последнего по времени документа, без
    // фильтра по статусу — черновик, привязанный к организации, не может
    // существовать в неудалённом виде с "мусорной" ошибочной ценой, т.к.
    // удаление документа каскадно чистит всё без следа).
    case 'document_info_data':
        $id = (int)($_POST['id'] ?? 0);

        $stmt = fncQuery(
            "SELECT d.id, d.number, d.document_date, d.status, d.sum, d.tax_sum,
                    d.organization_id, d.counterparty_id, d.warehouse_out_id, d.warehouse_in_id,
                    dt.code, dt.name AS type_name, dt.direction, dt.warehouse_count, dt.warehouse_field,
                    o.name AS organization_name, oot.abbreviation AS organization_abbreviation, oot.is_individual AS organization_is_individual,
                    c.name AS counterparty_name, cot.abbreviation AS counterparty_abbreviation, cot.is_individual AS counterparty_is_individual,
                    wo.name AS warehouse_out_name,
                    wi.name AS warehouse_in_name
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             LEFT JOIN organizations o ON o.id = d.organization_id
             LEFT JOIN organization_types oot ON oot.id = o.organization_type_id
             LEFT JOIN organizations c ON c.id = d.counterparty_id
             LEFT JOIN organization_types cot ON cot.id = c.organization_type_id
             LEFT JOIN objects wo ON wo.id = d.warehouse_out_id
             LEFT JOIN objects wi ON wi.id = d.warehouse_in_id
             WHERE d.id = :id",
            ['id' => $id]
        );
        $info = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        if (empty($info)) {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не найден']);
            exit;
        }

        $info['organization_display_name'] = fncOrgDisplayName($info['organization_name'], $info['organization_abbreviation'], $info['organization_is_individual']);
        $info['counterparty_display_name']  = fncOrgDisplayName($info['counterparty_name'], $info['counterparty_abbreviation'], $info['counterparty_is_individual']);

        $stmt = fncQuery(
            "SELECT di.id, di.nomenclature_id, n.name AS nomenclature_name,
                    di.quantity, di.price, di.sum, di.tax_sum, u.short_name AS unit_short_name
             FROM document_items di
             LEFT JOIN nomenclature n ON n.id = di.nomenclature_id
             LEFT JOIN units u ON u.id = n.unit_id
             WHERE di.document_id = :id
             ORDER BY di.created_at",
            ['id' => $id]
        );
        $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $is_empty = empty($items);

        $organizations = [];
        $warehouses = [];
        $counterparties = [];
        $nomenclature = [];

        if ($info['status'] === 'draft' && $is_empty) {
            $stmt = fncQuery(
                "SELECT o.id, o.name
                 FROM organizations o
                 WHERE o.is_contractor = 0 AND o.is_active = 1
                   AND EXISTS (SELECT 1 FROM objects ob WHERE ob.organization_id = o.id AND ob.is_stock = 1 AND ob.is_active = 1)
                 ORDER BY o.name"
            );
            $organizations = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $stmt = fncQuery(
                "SELECT ob.id, ob.name, ob.organization_id
                 FROM objects ob
                 WHERE ob.is_stock = 1 AND ob.is_active = 1
                 ORDER BY ob.name"
            );
            $warehouses = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            if (in_array($info['direction'], ['incoming', 'outgoing'], true)) {
                $stmt = fncQuery(
                    "SELECT o.id, o.name, ot.abbreviation, ot.is_individual
                     FROM organizations o
                     LEFT JOIN organization_types ot ON ot.id = o.organization_type_id
                     WHERE o.is_contractor = 1 AND o.is_active = 1
                     ORDER BY o.name"
                );
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                foreach ($rows as $row) {
                    $counterparties[] = [
                        'id' => $row['id'],
                        'display_name' => fncOrgDisplayName($row['name'], $row['abbreviation'], $row['is_individual']),
                    ];
                }
            }
        }

        if ($info['status'] === 'draft') {
            if ($info['direction'] === 'incoming') {
                $stmt = fncQuery(
                    "SELECT n.id, n.name,
                            (SELECT di.price
                             FROM document_items di
                             INNER JOIN documents d2 ON d2.id = di.document_id
                             WHERE di.nomenclature_id = n.id AND d2.organization_id = :organization_id
                             ORDER BY di.created_at DESC
                             LIMIT 1) AS last_price
                     FROM nomenclature n
                     WHERE n.is_purchased = 1 AND n.is_active = 1
                     ORDER BY n.name",
                    ['organization_id' => $info['organization_id']]
                );
            } else {
                $stmt = fncQuery(
                    "SELECT id, name FROM nomenclature WHERE (is_purchased = 1 OR is_produced = 1) AND is_active = 1 ORDER BY name"
                );
            }
            $nomenclature = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        $result = [
            'info'           => $info,
            'items'          => $items,
            'is_empty'       => $is_empty,
            'organizations'  => $organizations,
            'warehouses'     => $warehouses,
            'counterparties' => $counterparties,
            'nomenclature'   => $nomenclature,
        ];
        break;

    //==========================================================================
    // Редактирование шапки документа. Организация/склад/контрагент/сумма/налог
    // редактируются ТОЛЬКО если у документа ещё нет строк — иначе игнорируем
    // эти параметры, даже если они пришли в запросе (защита на бэкенде, не
    // только на фронте). Номер/дата — только для incoming, редактируются всегда,
    // пока документ draft. При смене склада — та же проверка доступности,
    // что и при создании документа (блокировка FOR UPDATE + проверка на
    // незавершённые документы), выполняется ВСЕГДА при is_empty, независимо
    // от того, реально ли изменился склад — перестраховка важнее экономии
    // одного запроса.
    case 'upd_document_header':
        $id = (int)fncValFind('id', $params);
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан документ']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.id, d.status, d.warehouse_out_id, d.warehouse_in_id,
                    dt.code, dt.direction, dt.warehouse_count, dt.warehouse_field
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE d.id = :id",
            ['id' => $id]
        );
        $doc = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$doc) {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не найден']);
            exit;
        }
        if ($doc['status'] !== 'draft') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ уже проведён']);
            exit;
        }
        if (!fncCan($perms, 'inventory.documents.' . $doc['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $stmt = fncQuery("SELECT COUNT(*) AS cnt FROM document_items WHERE document_id = :id", ['id' => $id]);
        $is_empty = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] === 0 : false;

        $update_fields = [];
        $update_binds  = ['id' => $id];

        if ($doc['direction'] === 'incoming') {
            $number = fncValFind('number', $params);
            $document_date = fncValFind('document_date', $params);
            if (!$number || !$document_date) {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните номер и дату документа']);
                exit;
            }
            $update_fields[] = 'number = :number';
            $update_fields[] = 'document_date = :document_date';
            $update_binds['number'] = $number;
            $update_binds['document_date'] = $document_date;
        }

        $new_warehouse_out_id = $doc['warehouse_out_id'];
        $new_warehouse_in_id  = $doc['warehouse_in_id'];

        if ($is_empty) {
            $organization_id = (int)fncValFind('organization_id', $params);
            if (!$organization_id) {
                echo json_encode(['sccss' => false, 'msg' => 'Выберите организацию']);
                exit;
            }
            $update_fields[] = 'organization_id = :organization_id';
            $update_binds['organization_id'] = $organization_id;

            if ((int)$doc['warehouse_count'] === 2) {
                $new_warehouse_out_id = (int)fncValFind('warehouse_out_id', $params) ?: null;
                $new_warehouse_in_id  = (int)fncValFind('warehouse_in_id', $params) ?: null;
                if (!$new_warehouse_out_id || !$new_warehouse_in_id) {
                    echo json_encode(['sccss' => false, 'msg' => 'Выберите оба склада']);
                    exit;
                }
                if ($new_warehouse_out_id === $new_warehouse_in_id) {
                    echo json_encode(['sccss' => false, 'msg' => 'Склады должны отличаться']);
                    exit;
                }
            } elseif ($doc['warehouse_field'] === 'out') {
                $new_warehouse_out_id = (int)fncValFind('warehouse_out_id', $params) ?: null;
                if (!$new_warehouse_out_id) {
                    echo json_encode(['sccss' => false, 'msg' => 'Выберите склад списания']);
                    exit;
                }
            } elseif ($doc['warehouse_field'] === 'in') {
                $new_warehouse_in_id = (int)fncValFind('warehouse_in_id', $params) ?: null;
                if (!$new_warehouse_in_id) {
                    echo json_encode(['sccss' => false, 'msg' => 'Выберите склад поступления']);
                    exit;
                }
                if ($doc['code'] === 'sf_production') {
                    $new_warehouse_out_id = $new_warehouse_in_id;
                }
            }
            $update_fields[] = 'warehouse_out_id = :warehouse_out_id';
            $update_fields[] = 'warehouse_in_id = :warehouse_in_id';
            $update_binds['warehouse_out_id'] = $new_warehouse_out_id;
            $update_binds['warehouse_in_id']  = $new_warehouse_in_id;

            if (in_array($doc['direction'], ['incoming', 'outgoing'], true)) {
                $counterparty_id = (int)fncValFind('counterparty_id', $params) ?: null;
                if (!$counterparty_id) {
                    echo json_encode(['sccss' => false, 'msg' => 'Выберите контрагента']);
                    exit;
                }
                $update_fields[] = 'counterparty_id = :counterparty_id';
                $update_binds['counterparty_id'] = $counterparty_id;
            }

            if ($doc['direction'] === 'incoming') {
                $sum = fncValFind('sum', $params);
                $tax_sum = fncValFind('tax_sum', $params);
                if ($sum === null || $sum === '') {
                    echo json_encode(['sccss' => false, 'msg' => 'Укажите сумму']);
                    exit;
                }
                $update_fields[] = 'sum = :sum';
                $update_fields[] = 'tax_sum = :tax_sum';
                $update_fields[] = 'outstanding_sum = :outstanding_sum';
                $update_binds['sum'] = $sum;
                $update_binds['tax_sum'] = $tax_sum;
                $update_binds['outstanding_sum'] = $sum;
            }
        }

        global $pdo;

        try {
            $pdo->beginTransaction();

            if ($is_empty) {
                $warehouse_ids = array_unique(array_filter([$new_warehouse_out_id, $new_warehouse_in_id]));

                foreach ($warehouse_ids as $wid) {
                    fncQuery("SELECT id FROM objects WHERE id = :id FOR UPDATE", ['id' => $wid]);
                }

                if (!empty($warehouse_ids)) {
                    $out_placeholders = [];
                    $in_placeholders  = [];
                    $binds = [];

                    foreach (array_values($warehouse_ids) as $i => $wid) {
                        $out_key = "out_wid{$i}";
                        $in_key  = "in_wid{$i}";
                        $out_placeholders[] = ":{$out_key}";
                        $in_placeholders[]  = ":{$in_key}";
                        $binds[$out_key] = $wid;
                        $binds[$in_key]  = $wid;
                    }
                    $binds['self_id'] = $id;

                    $out_sql = implode(',', $out_placeholders);
                    $in_sql  = implode(',', $in_placeholders);

                    $stmt = fncQuery(
                        "SELECT id FROM documents
                         WHERE id != :self_id
                           AND status IN ('draft','posting')
                           AND (warehouse_out_id IN ($out_sql) OR warehouse_in_id IN ($in_sql))
                         LIMIT 1",
                        $binds
                    );
                    $blocking = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
                    if ($blocking) {
                        $pdo->rollBack();
                        echo json_encode(['sccss' => false, 'msg' => 'По одному из выбранных складов уже есть незавершённый документ']);
                        exit;
                    }
                }
            }

            if (!empty($update_fields)) {
                $sql = "UPDATE documents SET " . implode(', ', $update_fields) . " WHERE id = :id";
                $stmt = fncQuery($sql, $update_binds);
                if (!$stmt) {
                    $pdo->rollBack();
                    echo json_encode(['sccss' => false, 'msg' => 'Не удалось сохранить изменения']);
                    exit;
                }
            }

            $pdo->commit();
            $result = ['sccss' => true];

        } catch (Exception $e) {
            $pdo->rollBack();
            fncLog('Ошибка обновления шапки документа: ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Произошла ошибка']);
            exit;
        }
        break;

    //==========================================================================
    // Добавление строки к черновику документа. Только пока status = draft.
    // Для incoming — цена/сумма/налог обязательны, вводятся вручную (последняя
    // цена подставляется на фронте из data-price справочника номенклатуры).
    // Для internal/outgoing — только количество, цена/сумма проставляются
    // при проведении. Номенклатура фильтруется по направлению: incoming —
    // только is_purchased, остальные (кроме sf_production, который отложен) —
    // is_purchased ИЛИ is_produced.
    case 'new_document_item':
        $document_id = (int)fncValFind('document_id', $params);
        if (!$document_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан документ']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.id, d.status, dt.code, dt.direction
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE d.id = :id",
            ['id' => $document_id]
        );
        $doc = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$doc) {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не найден']);
            exit;
        }
        if ($doc['status'] !== 'draft') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ уже проведён']);
            exit;
        }
        if (!fncCan($perms, 'inventory.documents.' . $doc['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $nomenclature_id = (int)fncValFind('nomenclature_id', $params);
        $quantity = fncValFind('quantity', $params);

        if (!$nomenclature_id || !$quantity || (float)$quantity <= 0) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        $nomenclature_filter = $doc['direction'] === 'incoming'
            ? "n.is_purchased = 1"
            : "(n.is_purchased = 1 OR n.is_produced = 1)";

        $stmt = fncQuery(
            "SELECT n.id FROM nomenclature n WHERE n.id = :id AND n.is_active = 1 AND {$nomenclature_filter}",
            ['id' => $nomenclature_id]
        );
        $valid_nomenclature = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$valid_nomenclature) {
            echo json_encode(['sccss' => false, 'msg' => 'Некорректная номенклатура']);
            exit;
        }

        $price = null;
        $sum = null;
        $tax_sum = null;

        if ($doc['direction'] === 'incoming') {
            $price = fncValFind('price', $params);
            $sum = fncValFind('sum', $params);
            $tax_sum = fncValFind('tax_sum', $params);

            if ($price === null || $price === '' || $sum === null || $sum === '' || $tax_sum === null || $tax_sum === '') {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
                exit;
            }
        }

        global $pdo;
        $stmt = fncQuery(
            "INSERT INTO document_items (document_id, nomenclature_id, quantity, price, sum, tax_sum, created_by)
             VALUES (:document_id, :nomenclature_id, :quantity, :price, :sum, :tax_sum, :created_by)",
            [
                'document_id'     => $document_id,
                'nomenclature_id' => $nomenclature_id,
                'quantity'        => $quantity,
                'price'           => $price,
                'sum'             => $sum,
                'tax_sum'         => $tax_sum,
                'created_by'      => $user_id,
            ]
        );

        $result = $stmt
            ? ['sccss' => true, 'id' => (int)$pdo->lastInsertId()]
            : ['sccss' => false, 'msg' => 'Не удалось добавить строку'];
        break;

    //==========================================================================
    // Физическое удаление строки документа. Только пока сам документ draft —
    // проверка идёт через JOIN на documents, а не по document_items напрямую.
    case 'del_document_item':
        $id = (int)(fncValFind('id', $params) ?? ($_POST['id'] ?? 0));
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана строка']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT di.id, d.status, dt.code
             FROM document_items di
             LEFT JOIN documents d ON d.id = di.document_id
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE di.id = :id",
            ['id' => $id]
        );
        $item = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$item) {
            echo json_encode(['sccss' => false, 'msg' => 'Строка не найдена']);
            exit;
        }
        if ($item['status'] !== 'draft') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ уже проведён']);
            exit;
        }
        if (!fncCan($perms, 'inventory.documents.' . $item['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $stmt = fncQuery("DELETE FROM document_items WHERE id = :id", ['id' => $id]);
        $result = ['sccss' => (bool)$stmt];
        break;

    //==========================================================================
    // Удаление черновика документа целиком. Только пока status = draft — на
    // проведённый документ действие недоступно ни при каких условиях. Каскады
    // (document_items, document_item_batches — ON DELETE CASCADE) подчищают
    // зависимые записи автоматически, без явного кода на удаление здесь.
    case 'del_document':
        $id = (int)(fncValFind('id', $params) ?? ($_POST['id'] ?? 0));
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан документ']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.id, d.status, dt.code
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE d.id = :id",
            ['id' => $id]
        );
        $doc = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$doc) {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не найден']);
            exit;
        }
        if ($doc['status'] !== 'draft') {
            echo json_encode(['sccss' => false, 'msg' => 'Можно удалять только черновики']);
            exit;
        }
        if (!fncCan($perms, 'inventory.documents.' . $doc['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $stmt = fncQuery("DELETE FROM documents WHERE id = :id", ['id' => $id]);
        $result = ['sccss' => (bool)$stmt];
        break;

    //==========================================================================
    // Данные одной строки документа для формы редактирования (офканвас).
    // Отдаёт document_tax_sum отдельно от tax_sum самой строки — на фронте
    // нужно знать именно налог ШАПКИ документа, чтобы решить, показывать ли
    // поле НДС строки как disabled.
    case 'document_item_info':
        $id = (int)($_POST['id'] ?? 0);

        $stmt = fncQuery(
            "SELECT di.id, di.nomenclature_id, n.name AS nomenclature_name,
                    di.quantity, di.price, di.sum, di.tax_sum,
                    d.tax_sum AS document_tax_sum, dt.direction
             FROM document_items di
             LEFT JOIN nomenclature n ON n.id = di.nomenclature_id
             LEFT JOIN documents d ON d.id = di.document_id
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE di.id = :id",
            ['id' => $id]
        );
        $item = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        if (empty($item)) {
            echo json_encode(['sccss' => false, 'msg' => 'Строка не найдена']);
            exit;
        }

        $item['is_incoming'] = $item['direction'] === 'incoming';

        $result = $item;
        break;

    //==========================================================================
    // Редактирование строки документа. Только пока сам документ draft.
    // Номенклатура строки не редактируется (создание новой позиции при смене
    // номенклатуры логичнее, чем превращение одной строки в другую). Количество
    // обязательно всегда; цена/сумма/налог — только для incoming.
    case 'upd_document_item':
        $id = (int)fncValFind('id', $params);
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана строка']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT di.id, d.status, dt.code, dt.direction
             FROM document_items di
             LEFT JOIN documents d ON d.id = di.document_id
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE di.id = :id",
            ['id' => $id]
        );
        $item = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$item) {
            echo json_encode(['sccss' => false, 'msg' => 'Строка не найдена']);
            exit;
        }
        if ($item['status'] !== 'draft') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ уже проведён']);
            exit;
        }
        if (!fncCan($perms, 'inventory.documents.' . $item['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $quantity = fncValFind('quantity', $params);
        if (!$quantity || (float)$quantity <= 0) {
            echo json_encode(['sccss' => false, 'msg' => 'Укажите количество']);
            exit;
        }

        $update_fields = ['quantity = :quantity'];
        $update_binds = ['id' => $id, 'quantity' => $quantity];

        if ($item['direction'] === 'incoming') {
            $price = fncValFind('price', $params);
            $sum = fncValFind('sum', $params);
            $tax_sum = fncValFind('tax_sum', $params);

            if ($price === null || $price === '' || $sum === null || $sum === '' || $tax_sum === null || $tax_sum === '') {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
                exit;
            }

            $update_fields[] = 'price = :price';
            $update_fields[] = 'sum = :sum';
            $update_fields[] = 'tax_sum = :tax_sum';
            $update_binds['price'] = $price;
            $update_binds['sum'] = $sum;
            $update_binds['tax_sum'] = $tax_sum;
        }

        $sql = "UPDATE document_items SET " . implode(', ', $update_fields) . " WHERE id = :id";
        $stmt = fncQuery($sql, $update_binds);

        $result = ['sccss' => (bool)$stmt];
        break;

    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
