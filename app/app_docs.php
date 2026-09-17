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

// Общее условие "цена/сумма вводятся вручную в строке" — incoming (всегда)
// или конкретно Оприходование (единственный internal-тип, где приход не
// имеет источника цены из прошлых проведений). Остальные internal-типы
// получают цену через FIFO при проведении, здесь не участвуют.
function fncItemRequiresPrice($direction, $code) {
    return $direction === 'incoming' || $code === 'stock_adjustment';
}

// Проверяет, что quantity не превышает доступный остаток номенклатуры на
// складе списания, с учётом количества, уже "занятого" другими строками
// этого же документа (exclude_item_id — исключить из подсчёта саму
// редактируемую строку, чтобы не вычесть её значение дважды).
// Возвращает true, если остатка достаточно, false — если нет.
function fncCheckStockAvailability($nomenclature_id, $warehouse_id, $document_id, $quantity, $exclude_item_id = null) {
    $stmt = fncQuery(
        "SELECT total_quantity FROM stock_ledger
         WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id AND is_last = 1
         LIMIT 1",
        ['nomenclature_id' => $nomenclature_id, 'warehouse_id' => $warehouse_id]
    );
    $stock_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    $available = $stock_row ? (float)$stock_row['total_quantity'] : 0;

    $exclude_sql = $exclude_item_id ? " AND id != :exclude_id" : "";
    $binds = ['document_id' => $document_id, 'nomenclature_id' => $nomenclature_id];
    if ($exclude_item_id) {
        $binds['exclude_id'] = $exclude_item_id;
    }

    $stmt = fncQuery(
        "SELECT COALESCE(SUM(quantity), 0) AS already_reserved
         FROM document_items
         WHERE document_id = :document_id AND nomenclature_id = :nomenclature_id{$exclude_sql}",
        $binds
    );
    $already_reserved = $stmt ? (float)$stmt->fetch(PDO::FETCH_ASSOC)['already_reserved'] : 0;

    return (float)$quantity <= ($available - $already_reserved);
}

// Общий первый шаг для fncStockLedgerIn/fncStockLedgerOut: читает текущий
// is_last-остаток по номенклатуре/складу и снимает с него флаг (новая итоговая
// запись будет вставлена вызывающим кодом). Возвращает [prev_total_quantity,
// prev_total_sum] (нули, если остатка ещё не было) или false при ошибке
// запроса — вызывающий код должен сам проверить результат на false.
function fncFlipLastStockLedger($nomenclature_id, $warehouse_id) {
    $stmt = fncQuery(
        "SELECT id, total_quantity, total_sum
         FROM stock_ledger
         WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id AND is_last = 1
         LIMIT 1",
        ['nomenclature_id' => $nomenclature_id, 'warehouse_id' => $warehouse_id]
    );
    if (!$stmt) return false;
    $prev = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prev) {
        $stmt = fncQuery("UPDATE stock_ledger SET is_last = 0 WHERE id = :id", ['id' => $prev['id']]);
        if (!$stmt) return false;
    }

    return [
        $prev ? (float)$prev['total_quantity'] : 0.0,
        $prev ? (float)$prev['total_sum'] : 0.0,
    ];
}

// Приход одной позиции на склад: находит текущий is_last-остаток номенклатуры
// на складе, снимает с него флаг, создаёт новую запись с пересчитанным
// итогом. Используется при проведении ЛЮБОГО документа с приходной частью —
// все incoming-типы (УПД/накладная/чек) целиком, Оприходование целиком,
// приходная сторона Перемещения, приход готового ПФ при Выпуске
// полуфабрикатов. Вызывающий код обязан находиться внутри уже открытой
// транзакции и сам проверять результат на false — функция не бросает
// исключений, только пробрасывает false от fncQuery дальше.
function fncStockLedgerIn($document_id, $document_type_id, $document_item_id, $warehouse_id, $nomenclature_id, $quantity, $price, $sum) {
    $prev_totals = fncFlipLastStockLedger($nomenclature_id, $warehouse_id);
    if ($prev_totals === false) return false;
    [$prev_quantity, $prev_sum] = $prev_totals;

    $new_total_quantity = $prev_quantity + (float)$quantity;
    $new_total_sum = $prev_sum + (float)$sum;

    return fncQuery(
        "INSERT INTO stock_ledger
            (document_id, document_type_id, document_item_id, warehouse_id, nomenclature_id,
             date, quantity, price, sum, quantity_remaining, total_quantity, total_sum, is_last)
         VALUES
            (:document_id, :document_type_id, :document_item_id, :warehouse_id, :nomenclature_id,
             NOW(), :quantity, :price, :sum, :quantity_remaining, :total_quantity, :total_sum, 1)",
        [
            'document_id'        => $document_id,
            'document_type_id'   => $document_type_id,
            'document_item_id'   => $document_item_id,
            'warehouse_id'       => $warehouse_id,
            'nomenclature_id'    => $nomenclature_id,
            'quantity'           => $quantity,
            'price'              => $price,
            'sum'                => $sum,
            'quantity_remaining' => $quantity,
            'total_quantity'     => $new_total_quantity,
            'total_sum'          => $new_total_sum,
        ]
    );
}
// Расход одной строки документа со склада: читает уже сохранённое
// распределение по партиям (document_item_batches), для каждой партии
// создаёт запись в stock_ledger_consumption и уменьшает её
// quantity_remaining, затем создаёт ОДНУ расходную запись в stock_ledger
// на всю строку целиком (не на партию), с ценой = средневзвешенной по
// факту списанных партий. Итоговые total_quantity/total_sum и
// quantity_remaining партий округляются до 0, если попадают в пределы
// погрешности (0.001) — чтобы полностью выбранный остаток не оставался
// "почти нулевым" из-за накопленной арифметики с плавающей точкой.
// Возвращает сумму списания строки (float) при успехе, false при ошибке —
// вызывающий код накапливает эту сумму в итог документа, раз document_items
// для расходных строк не хранит sum заранее (заполняется только здесь).
function fncStockLedgerOut($document_id, $document_type_id, $document_item_id, $warehouse_id, $nomenclature_id, $quantity) {
    global $pdo;

    $stmt = fncQuery(
        "SELECT id, batch_ledger_id, quantity FROM document_item_batches WHERE document_item_id = :document_item_id",
        ['document_item_id' => $document_item_id]
    );
    if (!$stmt) return false;
    $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($distribution)) return false;

    $total_sum = 0;
    $consumptions = [];

    foreach ($distribution as $d) {
        $stmt = fncQuery(
            "SELECT price, quantity_remaining FROM stock_ledger WHERE id = :id",
            ['id' => $d['batch_ledger_id']]
        );
        if (!$stmt) return false;
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) return false;

        $take_quantity = (float)$d['quantity'];
        $batch_price = (float)$batch['price'];
        $take_sum = round($take_quantity * $batch_price, 2);

        $new_remaining = round((float)$batch['quantity_remaining'] - $take_quantity, 3);
        if (abs($new_remaining) < 0.001) {
            $new_remaining = 0;
        }

        $stmt = fncQuery(
            "UPDATE stock_ledger SET quantity_remaining = :remaining WHERE id = :id",
            ['remaining' => $new_remaining, 'id' => $d['batch_ledger_id']]
        );
        if (!$stmt) return false;

        $consumptions[] = ['batch_ledger_id' => $d['batch_ledger_id'], 'quantity' => $take_quantity, 'price' => $batch_price, 'sum' => $take_sum];
        $total_sum += $take_sum;
    }

    $avg_price = (float)$quantity > 0 ? round($total_sum / (float)$quantity, 4) : 0;

    $prev_totals = fncFlipLastStockLedger($nomenclature_id, $warehouse_id);
    if ($prev_totals === false) return false;
    [$prev_quantity, $prev_sum] = $prev_totals;

    $new_total_quantity = round($prev_quantity - (float)$quantity, 3);
    if (abs($new_total_quantity) < 0.001) {
        $new_total_quantity = 0;
    }

    $new_total_sum = round($prev_sum - $total_sum, 2);
    if (abs($new_total_sum) < 0.001) {
        $new_total_sum = 0;
    }

    $stmt = fncQuery(
        "INSERT INTO stock_ledger
            (document_id, document_type_id, document_item_id, warehouse_id, nomenclature_id,
             date, quantity, price, sum, total_quantity, total_sum, is_last)
         VALUES
            (:document_id, :document_type_id, :document_item_id, :warehouse_id, :nomenclature_id,
             NOW(), :quantity, :price, :sum, :total_quantity, :total_sum, 1)",
        [
            'document_id'      => $document_id,
            'document_type_id' => $document_type_id,
            'document_item_id' => $document_item_id,
            'warehouse_id'     => $warehouse_id,
            'nomenclature_id'  => $nomenclature_id,
            'quantity'         => -1 * (float)$quantity,
            'price'            => $avg_price,
            'sum'              => -1 * $total_sum,
            'total_quantity'   => $new_total_quantity,
            'total_sum'        => $new_total_sum,
        ]
    );
    if (!$stmt) return false;
    $new_ledger_id = (int)$pdo->lastInsertId();

    foreach ($consumptions as $c) {
        $stmt = fncQuery(
            "INSERT INTO stock_ledger_consumption (ledger_id, batch_ledger_id, quantity, price, sum)
             VALUES (:ledger_id, :batch_ledger_id, :quantity, :price, :sum)",
            [
                'ledger_id'       => $new_ledger_id,
                'batch_ledger_id' => $c['batch_ledger_id'],
                'quantity'        => $c['quantity'],
                'price'           => $c['price'],
                'sum'             => $c['sum'],
            ]
        );
        if (!$stmt) return false;
    }

    // Записываем фактическую цену/сумму списания в саму строку документа —
    // до проведения price/sum расходной строки всегда NULL (мы решали, что
    // цена появляется только по факту проведения), после проведения строка
    // должна отражать реальную среднюю цену и сумму списания.
    $stmt = fncQuery(
        "UPDATE document_items SET price = :price, sum = :sum WHERE id = :id",
        ['price' => $avg_price, 'sum' => $total_sum, 'id' => $document_item_id]
    );
    if (!$stmt) return false;

    return $total_sum;
}
// Автоматическое FIFO-распределение количества по доступным партиям.
// Возвращает массив [{batch_id, quantity}, ...] либо false, если суммарного
// остатка по всем партиям недостаточно для покрытия запрошенного количества.
// $document_id обязателен: без него распределение видело бы "сырой"
// quantity_remaining партии и могло бы выдать одну и ту же партию второй
// строке этого же документа, которая её уже забронировала (quantity_remaining
// уменьшается только при проведении, не при добавлении строки-черновика).
function fncAutoDistributeBatches($nomenclature_id, $warehouse_id, $quantity, $document_id) {
    $stmt = fncQuery(
        "SELECT id, quantity_remaining
         FROM stock_ledger
         WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id
           AND quantity_remaining > 0
         ORDER BY date ASC, id ASC",
        ['nomenclature_id' => $nomenclature_id, 'warehouse_id' => $warehouse_id]
    );
    if (!$stmt) return false;
    $available_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reservations = fncBatchReservationsInDocument($document_id);

    $remaining_to_distribute = (float)$quantity;
    $distribution = [];

    foreach ($available_batches as $batch) {
        if ($remaining_to_distribute <= 0) break;

        $net_remaining = (float)$batch['quantity_remaining'] - ($reservations[(int)$batch['id']] ?? 0);
        if ($net_remaining <= 0) continue;

        $take = min($remaining_to_distribute, $net_remaining);
        $distribution[] = ['batch_id' => (int)$batch['id'], 'quantity' => $take];
        $remaining_to_distribute -= $take;
    }

    if ($remaining_to_distribute > 0.001) {
        return false;
    }

    return $distribution;
}

// Сколько из каждой партии уже занято ДРУГИМИ строками этого же документа
// (document_item_batches) — партии физически резервируются в момент
// добавления строки-черновика, а списываются с quantity_remaining только при
// проведении, поэтому без этого две строки одного документа могли бы
// "выбрать" одну и ту же партию дважды. Возвращает map batch_ledger_id =>
// количество. Гонка МЕЖДУ документами архитектурно исключена (один активный
// документ на склад), поэтому учитывать другие документы здесь не нужно.
function fncBatchReservationsInDocument($document_id, $exclude_item_id = null) {
    $exclude_sql = $exclude_item_id ? " AND di.id != :exclude_item_id" : "";
    $binds = ['document_id' => $document_id];
    if ($exclude_item_id) {
        $binds['exclude_item_id'] = $exclude_item_id;
    }
    $stmt = fncQuery(
        "SELECT dib.batch_ledger_id, SUM(dib.quantity) AS reserved
         FROM document_item_batches dib
         INNER JOIN document_items di ON di.id = dib.document_item_id
         WHERE di.document_id = :document_id{$exclude_sql}
         GROUP BY dib.batch_ledger_id",
        $binds
    );
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $map = [];
    foreach ($rows as $row) {
        $map[(int)$row['batch_ledger_id']] = (float)$row['reserved'];
    }
    return $map;
}

// Валидирует ручное распределение количества строки по партиям: партии
// существуют на нужном складе, остаток каждой партии (за вычетом уже занятого
// другими строками этого документа) не превышен, сумма по партиям совпадает
// с количеством строки. Одним запросом на все партии сразу, а не по одной.
// Возвращает null при успехе, иначе текст ошибки для ответа клиенту.
function fncValidateManualBatches(array $batches, $warehouse_id, $document_id, $quantity) {
    $batch_ids = [];
    foreach ($batches as $b) {
        if (!isset($b['batch_id']) || !isset($b['quantity']) || (float)$b['quantity'] <= 0) {
            return 'Некорректное распределение по партиям';
        }
        $batch_ids[] = (int)$b['batch_id'];
    }
    $batch_ids = array_values(array_unique($batch_ids));

    $placeholders = [];
    $binds = ['warehouse_id' => $warehouse_id];
    foreach ($batch_ids as $i => $bid) {
        $key = "bid{$i}";
        $placeholders[] = ":{$key}";
        $binds[$key] = $bid;
    }
    $stmt = fncQuery(
        "SELECT id, quantity_remaining FROM stock_ledger WHERE id IN (" . implode(',', $placeholders) . ") AND warehouse_id = :warehouse_id",
        $binds
    );
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $remaining_by_batch = [];
    foreach ($rows as $row) {
        $remaining_by_batch[(int)$row['id']] = (float)$row['quantity_remaining'];
    }

    $reservations = fncBatchReservationsInDocument($document_id);

    $batches_sum = 0;
    foreach ($batches as $b) {
        $batch_id = (int)$b['batch_id'];
        if (!isset($remaining_by_batch[$batch_id])) {
            return 'Партия не найдена';
        }

        $net_remaining = $remaining_by_batch[$batch_id] - ($reservations[$batch_id] ?? 0);
        if ((float)$b['quantity'] > $net_remaining) {
            return 'Количество по партии превышает её остаток';
        }

        $batches_sum += (float)$b['quantity'];
    }

    if (abs($batches_sum - (float)$quantity) > 0.001) {
        return 'Сумма по партиям не совпадает с количеством строки';
    }

    return null;
}

// Шапка документа + справочники для редактируемых полей (только если документ
// ещё пуст). $include_nomenclature добавляет номенклатуру с подзапросами
// последней цены/остатка — нужна только форме добавления строки, не форме
// редактирования шапки. Возвращает null, если документ не найден.
function fncFetchDocumentHeaderData($id, $include_nomenclature) {
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
        return null;
    }

    $info['organization_display_name'] = fncOrgDisplayName($info['organization_name'], $info['organization_abbreviation'], $info['organization_is_individual']);
    $info['counterparty_display_name']  = fncOrgDisplayName($info['counterparty_name'], $info['counterparty_abbreviation'], $info['counterparty_is_individual']);
    // Единственный источник правды для "нужен ли выбор партий у строки" —
    // тот же fncItemRequiresPrice, что использует new_document_item. Фронт
    // читает этот флаг вместо повторного вывода правила по коду типа.
    $info['needs_batches'] = (bool)$info['warehouse_out_id'] && !fncItemRequiresPrice($info['direction'], $info['code']);

    $stmt = fncQuery("SELECT COUNT(*) AS cnt FROM document_items WHERE document_id = :id", ['id' => $id]);
    $is_empty = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] === 0 : false;

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

    if ($include_nomenclature && $info['status'] === 'draft') {
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
        } elseif ($info['warehouse_out_id']) {
            $stmt = fncQuery(
                "SELECT n.id, n.name,
                        (SELECT sl.total_quantity
                         FROM stock_ledger sl
                         WHERE sl.nomenclature_id = n.id AND sl.warehouse_id = :warehouse_id AND sl.is_last = 1
                         LIMIT 1) AS stock_quantity
                 FROM nomenclature n
                 WHERE (n.is_purchased = 1 OR n.is_produced = 1) AND n.is_active = 1
                 ORDER BY n.name",
                ['warehouse_id' => $info['warehouse_out_id']]
            );
        } else {
            $stmt = fncQuery(
                "SELECT id, name FROM nomenclature WHERE (is_purchased = 1 OR is_produced = 1) AND is_active = 1 ORDER BY name"
            );
        }
        $nomenclature = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    return [
        'info'           => $info,
        'is_empty'       => $is_empty,
        'organizations'  => $organizations,
        'warehouses'     => $warehouses,
        'counterparties' => $counterparties,
        'nomenclature'   => $nomenclature,
    ];
}
// Проводка взаиморасчётов с контрагентом при проведении документа. Знак
// delta_sum уже включает направление (см. конвенцию: мы платим/отгружаем —
// плюс, мы получаем товар/деньги — минус). Блокирует строку контрагента
// (organizations) на время чтения-изменения-записи total_balance — защита
// от гонки, когда два документа одного контрагента (на разных складах,
// иначе конфликтовали бы уже на уровне склада) проводятся одновременно.
// Возвращает true при успехе, false при ошибке.
function fncSettlementsLedgerPost($document_id, $organization_id, $counterparty_id, $delta_sum) {
    $lock_name = "settlements:{$organization_id}:{$counterparty_id}";

    $stmt = fncQuery("SELECT GET_LOCK(:lock_name, 10) AS locked", ['lock_name' => $lock_name]);
    $locked = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!$locked || (int)$locked['locked'] !== 1) {
        return false;
    }

    $stmt = fncQuery(
        "SELECT id, total_balance FROM settlements_ledger
         WHERE organization_id = :organization_id AND counterparty_id = :counterparty_id AND is_last = 1
         LIMIT 1",
        ['organization_id' => $organization_id, 'counterparty_id' => $counterparty_id]
    );
    if (!$stmt) {
        fncQuery("SELECT RELEASE_LOCK(:lock_name)", ['lock_name' => $lock_name]);
        return false;
    }
    $prev = $stmt->fetch(PDO::FETCH_ASSOC);
    $prev_balance = $prev ? (float)$prev['total_balance'] : 0;

    $new_balance = round($prev_balance + (float)$delta_sum, 2);
    if (abs($new_balance) < 0.001) {
        $new_balance = 0;
    }

    if ($prev) {
        $stmt = fncQuery("UPDATE settlements_ledger SET is_last = 0 WHERE id = :id", ['id' => $prev['id']]);
        if (!$stmt) {
            fncQuery("SELECT RELEASE_LOCK(:lock_name)", ['lock_name' => $lock_name]);
            return false;
        }
    }

    $stmt = fncQuery(
        "INSERT INTO settlements_ledger (organization_id, counterparty_id, document_id, payment_id, date, sum, total_balance, is_last)
         VALUES (:organization_id, :counterparty_id, :document_id, NULL, NOW(), :sum, :total_balance, 1)",
        [
            'organization_id' => $organization_id,
            'counterparty_id' => $counterparty_id,
            'document_id'     => $document_id,
            'sum'             => $delta_sum,
            'total_balance'   => $new_balance,
        ]
    );

    fncQuery("SELECT RELEASE_LOCK(:lock_name)", ['lock_name' => $lock_name]);

    return $stmt ? true : false;
}

// Перемещение одной строки документа между складами: читает уже
// сохранённое распределение по партиям (document_item_batches, тот же
// механизм, что и для Списания), и для КАЖДОЙ партии создаёт ОТДЕЛЬНУЮ
// пару записей — расходную на складе-источнике (через fncStockLedgerOut
// для одной конкретной партии) и приходную на складе-получателе с той же
// ценой/количеством, связанную через source_ledger_id. В отличие от
// обычного Списания, партии НЕ сводятся в одну усреднённую запись — здесь
// важно сохранить их различимость, чтобы FIFO на складе-получателе
// продолжал работать корректно с подлинными ценами каждой партии.
// Возвращает суммарную стоимость перемещённой строки (float) при успехе,
// false при ошибке.
function fncStockLedgerTransfer($document_id, $document_type_id, $document_item_id, $warehouse_out_id, $warehouse_in_id, $nomenclature_id) {
    global $pdo;

    $stmt = fncQuery(
        "SELECT id, batch_ledger_id, quantity FROM document_item_batches WHERE document_item_id = :document_item_id",
        ['document_item_id' => $document_item_id]
    );
    if (!$stmt) return false;
    $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($distribution)) return false;

    $total_sum = 0;

    foreach ($distribution as $d) {
        $stmt = fncQuery(
            "SELECT price, quantity_remaining FROM stock_ledger WHERE id = :id",
            ['id' => $d['batch_ledger_id']]
        );
        if (!$stmt) return false;
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) return false;

        $take_quantity = (float)$d['quantity'];
        $batch_price = (float)$batch['price'];
        $take_sum = round($take_quantity * $batch_price, 2);

        $new_remaining = round((float)$batch['quantity_remaining'] - $take_quantity, 3);
        if (abs($new_remaining) < 0.001) {
            $new_remaining = 0;
        }

        $stmt = fncQuery(
            "UPDATE stock_ledger SET quantity_remaining = :remaining WHERE id = :id",
            ['remaining' => $new_remaining, 'id' => $d['batch_ledger_id']]
        );
        if (!$stmt) return false;

        // Расходная запись на складе-источнике — по этой ОДНОЙ партии,
        // не сводная (в отличие от fncStockLedgerOut для обычного Списания).
        $stmt = fncQuery(
            "SELECT id, total_quantity, total_sum
             FROM stock_ledger
             WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id AND is_last = 1
             LIMIT 1",
            ['nomenclature_id' => $nomenclature_id, 'warehouse_id' => $warehouse_out_id]
        );
        if (!$stmt) return false;
        $prev_out = $stmt->fetch(PDO::FETCH_ASSOC);
        $prev_out_quantity = $prev_out ? (float)$prev_out['total_quantity'] : 0;
        $prev_out_sum = $prev_out ? (float)$prev_out['total_sum'] : 0;

        $new_out_quantity = round($prev_out_quantity - $take_quantity, 3);
        if (abs($new_out_quantity) < 0.001) {
            $new_out_quantity = 0;
        }
        $new_out_sum = round($prev_out_sum - $take_sum, 2);
        if (abs($new_out_sum) < 0.001) {
            $new_out_sum = 0;
        }

        if ($prev_out) {
            $stmt = fncQuery("UPDATE stock_ledger SET is_last = 0 WHERE id = :id", ['id' => $prev_out['id']]);
            if (!$stmt) return false;
        }

        $stmt = fncQuery(
            "INSERT INTO stock_ledger
                (document_id, document_type_id, document_item_id, warehouse_id, nomenclature_id,
                 date, quantity, price, sum, total_quantity, total_sum, is_last)
             VALUES
                (:document_id, :document_type_id, :document_item_id, :warehouse_id, :nomenclature_id,
                 NOW(), :quantity, :price, :sum, :total_quantity, :total_sum, 1)",
            [
                'document_id'      => $document_id,
                'document_type_id' => $document_type_id,
                'document_item_id' => $document_item_id,
                'warehouse_id'     => $warehouse_out_id,
                'nomenclature_id'  => $nomenclature_id,
                'quantity'         => -1 * $take_quantity,
                'price'            => $batch_price,
                'sum'              => -1 * $take_sum,
                'total_quantity'   => $new_out_quantity,
                'total_sum'        => $new_out_sum,
            ]
        );
        if (!$stmt) return false;
        $out_ledger_id = (int)$pdo->lastInsertId();

        $stmt = fncQuery(
            "INSERT INTO stock_ledger_consumption (ledger_id, batch_ledger_id, quantity, price, sum)
             VALUES (:ledger_id, :batch_ledger_id, :quantity, :price, :sum)",
            [
                'ledger_id'       => $out_ledger_id,
                'batch_ledger_id' => $d['batch_ledger_id'],
                'quantity'        => $take_quantity,
                'price'           => $batch_price,
                'sum'             => $take_sum,
            ]
        );
        if (!$stmt) return false;

        // Приходная запись на складе-получателе — с той же ценой/количеством,
        // связана с расходной записью через source_ledger_id, создаёт НОВУЮ
        // партию (quantity_remaining = take_quantity) для дальнейшего FIFO
        // уже на складе-получателе.
        $stmt = fncQuery(
            "SELECT id, total_quantity, total_sum
             FROM stock_ledger
             WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id AND is_last = 1
             LIMIT 1",
            ['nomenclature_id' => $nomenclature_id, 'warehouse_id' => $warehouse_in_id]
        );
        if (!$stmt) return false;
        $prev_in = $stmt->fetch(PDO::FETCH_ASSOC);
        $prev_in_quantity = $prev_in ? (float)$prev_in['total_quantity'] : 0;
        $prev_in_sum = $prev_in ? (float)$prev_in['total_sum'] : 0;

        $new_in_quantity = $prev_in_quantity + $take_quantity;
        $new_in_sum = $prev_in_sum + $take_sum;

        if ($prev_in) {
            $stmt = fncQuery("UPDATE stock_ledger SET is_last = 0 WHERE id = :id", ['id' => $prev_in['id']]);
            if (!$stmt) return false;
        }

        $stmt = fncQuery(
            "INSERT INTO stock_ledger
                (document_id, document_type_id, document_item_id, warehouse_id, nomenclature_id,
                 date, quantity, price, sum, quantity_remaining, total_quantity, total_sum, is_last, source_ledger_id)
             VALUES
                (:document_id, :document_type_id, :document_item_id, :warehouse_id, :nomenclature_id,
                 NOW(), :quantity, :price, :sum, :quantity_remaining, :total_quantity, :total_sum, 1, :source_ledger_id)",
            [
                'document_id'        => $document_id,
                'document_type_id'   => $document_type_id,
                'document_item_id'   => $document_item_id,
                'warehouse_id'       => $warehouse_in_id,
                'nomenclature_id'    => $nomenclature_id,
                'quantity'           => $take_quantity,
                'price'              => $batch_price,
                'sum'                => $take_sum,
                'quantity_remaining' => $take_quantity,
                'total_quantity'     => $new_in_quantity,
                'total_sum'          => $new_in_sum,
                'source_ledger_id'   => $out_ledger_id,
            ]
        );
        if (!$stmt) return false;

        $total_sum += $take_sum;
    }

    return $total_sum;
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

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
    // Шапка документа + справочники для редактируемых полей (только если
    // документ ещё пуст) + номенклатура для формы добавления строки. Вызывается
    // при открытии карточки — тяжёлые подзапросы номенклатуры (последняя цена /
    // остаток на складе) не нужны при каждом обновлении списка строк, поэтому
    // вынесены из document_items_data в отдельный action. Форме редактирования
    // самой шапки (офканвас) номенклатура тоже не нужна — см. лёгкий вариант
    // document_header_edit_data ниже, который делит с этим action всю логику
    // через fncFetchDocumentHeaderData(), но пропускает нужные только для формы
    // добавления строки подзапросы.
    case 'document_header_data':
        $id = (int)($_POST['id'] ?? 0);

        $data = fncFetchDocumentHeaderData($id, true);
        if ($data === null) {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не найден']);
            exit;
        }
        if (!fncCan($perms, 'inventory.documents.' . $data['info']['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $result = $data;
        break;

    //==========================================================================
    // Лёгкая версия document_header_data для формы редактирования шапки
    // (офканвас) — тем же полям info/is_empty/organizations/warehouses/
    // counterparties, но без тяжёлых подзапросов номенклатуры, которые эта
    // форма никогда не использует.
    case 'document_header_edit_data':
        $id = (int)($_POST['id'] ?? 0);

        $data = fncFetchDocumentHeaderData($id, false);
        if ($data === null) {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не найден']);
            exit;
        }
        if (!fncCan($perms, 'inventory.documents.' . $data['info']['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $result = $data;
        break;

    //==========================================================================
    // Только строки документа + агрегаты (has_items/total_sum и т.д.) — лёгкий
    // action, вызываемый часто (при каждом добавлении/удалении/редактировании
    // строки), без тяжёлых подзапросов номенклатуры из document_header_data.
    case 'document_items_data':
        $id = (int)($_POST['id'] ?? 0);

        $stmt = fncQuery(
            "SELECT d.status, dt.code, dt.direction
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE d.id = :id",
            ['id' => $id]
        );
        $doc_check = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$doc_check) {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не найден']);
            exit;
        }
        if (!fncCan($perms, 'inventory.documents.' . $doc_check['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

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

        $result = [
            'items'       => $items,
            'status'      => $doc_check['status'],
            'is_incoming' => $doc_check['direction'] === 'incoming',
        ];
        break;

    //==========================================================================
    // Редактирование шапки документа. Организация/склад/контрагент/сумма/налог
    // редактируются ТОЛЬКО если у документа ещё нет строк — иначе игнорируем
    // эти параметры, даже если они пришли в запросе (защита на бэкенде, не
    // только на фронте). Номер/дата — только для incoming, редактируются всегда,
    // пока документ draft. При смене склада — та же проверка доступности,
    // что и при создании документа (блокировка FOR UPDATE + проверка на
    // незавершённые документы), выполняется ВСЕГДА при is_empty.
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

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            fncLog('Ошибка обновления шапки документа: ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Произошла ошибка']);
            exit;
        }
        break;

    //==========================================================================
    // Добавление строки к черновику документа. Только пока status = draft.
    // price/sum обязательны для incoming И для stock_adjustment (см.
    // fncItemRequiresPrice). tax_sum — только для incoming. Номенклатура
    // фильтруется по направлению: incoming — только is_purchased, остальные
    // (кроме sf_production, отложен) — is_purchased ИЛИ is_produced.
    // Партии (для складов списания без ручного ввода цены) — либо авто-FIFO,
    // либо явное ручное распределение с проверкой остатка каждой партии.
    case 'new_document_item':
        $document_id = (int)fncValFind('document_id', $params);
        if (!$document_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан документ']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.id, d.status, d.warehouse_out_id, dt.code, dt.direction
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

        if ($doc['warehouse_out_id']) {
            if (!fncCheckStockAvailability($nomenclature_id, $doc['warehouse_out_id'], $document_id, $quantity)) {
                echo json_encode(['sccss' => false, 'msg' => 'Количество превышает доступный остаток на складе']);
                exit;
            }
        }

        $price = null;
        $sum = null;
        $tax_sum = null;

        if (fncItemRequiresPrice($doc['direction'], $doc['code'])) {
            $price = fncValFind('price', $params);
            $sum = fncValFind('sum', $params);

            if ($price === null || $price === '' || $sum === null || $sum === '') {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
                exit;
            }
        }

        if ($doc['direction'] === 'incoming') {
            $tax_sum = fncValFind('tax_sum', $params);
            if ($tax_sum === null || $tax_sum === '') {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
                exit;
            }
        }

        $batches = null;
        $needs_batches = $doc['warehouse_out_id'] && !fncItemRequiresPrice($doc['direction'], $doc['code']);

        if ($needs_batches) {
            $auto_batch = fncValFind('auto_batch', $params);

            if ($auto_batch == '1' || $auto_batch === 1) {
                $batches = fncAutoDistributeBatches($nomenclature_id, $doc['warehouse_out_id'], $quantity, $document_id);
                if ($batches === false) {
                    echo json_encode(['sccss' => false, 'msg' => 'Недостаточно партий для автоматического распределения']);
                    exit;
                }
            } else {
                $batches_raw = fncValFind('batches', $params);
                if (!$batches_raw) {
                    echo json_encode(['sccss' => false, 'msg' => 'Не указано распределение по партиям']);
                    exit;
                }
                $batches = json_decode($batches_raw, true);
                if (!is_array($batches) || empty($batches)) {
                    echo json_encode(['sccss' => false, 'msg' => 'Некорректное распределение по партиям']);
                    exit;
                }
                $batches_error = fncValidateManualBatches($batches, $doc['warehouse_out_id'], $document_id, $quantity);
                if ($batches_error !== null) {
                    echo json_encode(['sccss' => false, 'msg' => $batches_error]);
                    exit;
                }
            }
        }

        global $pdo;

        try {
            $pdo->beginTransaction();

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
            if (!$stmt) {
                throw new Exception('Не удалось создать строку документа');
            }
            $new_item_id = (int)$pdo->lastInsertId();

            if ($batches !== null) {
                foreach ($batches as $b) {
                    $stmt = fncQuery(
                        "INSERT INTO document_item_batches (document_item_id, batch_ledger_id, quantity)
                         VALUES (:document_item_id, :batch_ledger_id, :quantity)",
                        [
                            'document_item_id' => $new_item_id,
                            'batch_ledger_id'  => (int)$b['batch_id'],
                            'quantity'         => $b['quantity'],
                        ]
                    );
                    if (!$stmt) {
                        throw new Exception('Не удалось сохранить распределение по партиям');
                    }
                }
            }

            $pdo->commit();
            $result = ['sccss' => true, 'id' => $new_item_id];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            fncLog('Ошибка добавления строки документа: ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Не удалось добавить строку']);
            exit;
        }
        break;

    //==========================================================================
    // Данные одной строки документа для формы редактирования (офканвас).
    // requires_price — то же условие, что и при добавлении (incoming или
    // stock_adjustment), решает видимость полей цена/сумма на фронте.
    // has_batches — строка со складом списания и без ручного ввода цены
    // (Списание/Перемещение) — такие строки не редактируются вообще, только
    // удаляются (см. upd_document_item).
    case 'document_item_info':
        $id = (int)($_POST['id'] ?? 0);

        $stmt = fncQuery(
            "SELECT di.id, di.nomenclature_id, n.name AS nomenclature_name,
                    di.quantity, di.price, di.sum, di.tax_sum,
                    d.tax_sum AS document_tax_sum, d.warehouse_out_id, dt.code, dt.direction
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
        if (!fncCan($perms, 'inventory.documents.' . $item['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $item['is_incoming'] = $item['direction'] === 'incoming';
        $item['requires_price'] = fncItemRequiresPrice($item['direction'], $item['code']);
        $item['has_batches'] = $item['warehouse_out_id'] && !$item['requires_price'];

        $result = $item;
        break;

    //==========================================================================
    // Список доступных партий (приходных записей stock_ledger с остатком) для
    // ручного распределения списания. Привязан к конкретному документу — не
    // только nomenclature_id+warehouse_id, чтобы нельзя было запросить данные
    // по произвольному складу без права на документ и без реальной связи
    // склада с этим документом. Сортировка по FIFO — старые партии первыми.
    case 'document_item_batches_data':
        $document_id = (int)($_POST['document_id'] ?? 0);
        $nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);
        $warehouse_id = (int)($_POST['warehouse_id'] ?? 0);

        if (!$document_id || !$nomenclature_id || !$warehouse_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указаны параметры']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.warehouse_out_id, dt.code
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
        if (!fncCan($perms, 'inventory.documents.' . $doc['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        if ((int)$doc['warehouse_out_id'] !== $warehouse_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Склад не соответствует документу']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT sl.id, sl.date, sl.price, sl.quantity_remaining, d.number AS document_number, dt.name AS document_type_name
             FROM stock_ledger sl
             LEFT JOIN documents d ON d.id = sl.document_id
             LEFT JOIN document_types dt ON dt.id = sl.document_type_id
             WHERE sl.nomenclature_id = :nomenclature_id AND sl.warehouse_id = :warehouse_id
               AND sl.quantity_remaining > 0
             ORDER BY sl.date ASC, sl.id ASC",
            ['nomenclature_id' => $nomenclature_id, 'warehouse_id' => $warehouse_id]
        );
        $batches_raw = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // Остаток каждой партии уменьшаем на уже занятое другими строками
        // этого же документа — иначе пикер предложит партию, которую другая
        // строка того же черновика уже выбрала (quantity_remaining ещё не
        // тронут, он уменьшается только при проведении).
        $reservations = fncBatchReservationsInDocument($document_id);

        $result = [];
        foreach ($batches_raw as $batch) {
            $net_remaining = round((float)$batch['quantity_remaining'] - ($reservations[(int)$batch['id']] ?? 0), 3);
            if ($net_remaining <= 0) continue;
            $batch['quantity_remaining'] = $net_remaining;
            $result[] = $batch;
        }
        break;

    //==========================================================================
    // Редактирование строки документа. Только пока сам документ draft.
    // Номенклатура строки не редактируется. Строки с распределением по партиям
    // (has_batches) не редактируются вообще — только удаление и создание
    // заново, чтобы не рассинхронизировать document_item_batches при смене
    // количества.
    case 'upd_document_item':
        $id = (int)fncValFind('id', $params);
        if (!$id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана строка']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT di.id, di.document_id, di.nomenclature_id, d.status, d.warehouse_out_id, dt.code, dt.direction
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

        $has_batches = $item['warehouse_out_id'] && !fncItemRequiresPrice($item['direction'], $item['code']);
        if ($has_batches) {
            echo json_encode(['sccss' => false, 'msg' => 'Редактирование строки с распределением по партиям недоступно, удалите и создайте заново']);
            exit;
        }

        $quantity = fncValFind('quantity', $params);
        if (!$quantity || (float)$quantity <= 0) {
            echo json_encode(['sccss' => false, 'msg' => 'Укажите количество']);
            exit;
        }

        if ($item['warehouse_out_id']) {
            if (!fncCheckStockAvailability($item['nomenclature_id'], $item['warehouse_out_id'], $item['document_id'], $quantity, $id)) {
                echo json_encode(['sccss' => false, 'msg' => 'Количество превышает доступный остаток на складе']);
                exit;
            }
        }

        $update_fields = ['quantity = :quantity'];
        $update_binds = ['id' => $id, 'quantity' => $quantity];

        if (fncItemRequiresPrice($item['direction'], $item['code'])) {
            $price = fncValFind('price', $params);
            $sum = fncValFind('sum', $params);

            if ($price === null || $price === '' || $sum === null || $sum === '') {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
                exit;
            }

            $update_fields[] = 'price = :price';
            $update_fields[] = 'sum = :sum';
            $update_binds['price'] = $price;
            $update_binds['sum'] = $sum;
        }

        if ($item['direction'] === 'incoming') {
            $tax_sum = fncValFind('tax_sum', $params);
            if ($tax_sum === null || $tax_sum === '') {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
                exit;
            }
            $update_fields[] = 'tax_sum = :tax_sum';
            $update_binds['tax_sum'] = $tax_sum;
        }

        $sql = "UPDATE document_items SET " . implode(', ', $update_fields) . " WHERE id = :id";
        $stmt = fncQuery($sql, $update_binds);

        $result = ['sccss' => (bool)$stmt];
        break;

    //==========================================================================
    // Физическое удаление строки документа. Только пока сам документ draft.
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
    // Удаление черновика документа целиком. Только пока status = draft.
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
    // Проведение документа. Шаг 1 — atomic check-and-set draft->posting,
    // отдельным коммитом. Шаг 2 — вся работа в одной транзакции; каждый
    // fncQuery проверяется на false и бросает исключение вручную — fncQuery
    // сама НЕ бросает исключений при ошибке SQL (только логирует и
    // возвращает false), поэтому без явной проверки catch не сработает
    // и документ зависнет в posting с неполными данными. catch (Throwable)
    // вместо (Exception) — перехватывает и Error/TypeError, не только
    // штатные исключения бизнес-логики.
    // Реализовано для stock_adjustment (приход, fncStockLedgerIn) и writeoff
    // (расход по FIFO/партиям, fncStockLedgerOut).
    case 'post_document':
        set_time_limit(600);

        $document_id = (int)fncValFind('id', $params);
        if (!$document_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан документ']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.id, d.status, d.organization_id, d.counterparty_id, d.sum,
                    d.warehouse_in_id, d.warehouse_out_id, d.document_type_id,
                    dt.code, dt.direction
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
        if (!fncCan($perms, 'inventory.documents.' . $doc['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $supported_codes = ['stock_adjustment', 'writeoff', 'transfer', 'incoming_upd', 'incoming_waybill', 'incoming_receipt'];
        if (!in_array($doc['code'], $supported_codes, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Проведение этого типа документа пока не реализовано']);
            exit;
        }
        if ($doc['status'] !== 'draft') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ уже проведён или проводится']);
            exit;
        }

        $stmt = fncQuery("SELECT COUNT(*) AS cnt FROM document_items WHERE document_id = :id", ['id' => $document_id]);
        $items_count = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] : 0;
        if ($items_count === 0) {
            echo json_encode(['sccss' => false, 'msg' => 'В документе нет строк']);
            exit;
        }

        // Шаг 1 — atomic check-and-set, отдельный коммит
        $stmt = fncQuery(
            "UPDATE documents SET status = 'posting', posting_started_at = NOW()
             WHERE id = :id AND status = 'draft'",
            ['id' => $document_id]
        );
        if (!$stmt || $stmt->rowCount() === 0) {
            echo json_encode(['sccss' => false, 'msg' => 'Документ уже проводится или недоступен']);
            exit;
        }

        global $pdo;

        try {
            $pdo->beginTransaction();

            $is_incoming  = $doc['direction'] === 'incoming';
            $is_outbound  = $doc['code'] === 'writeoff';
            $is_transfer  = $doc['code'] === 'transfer';

            // Перемещение блокирует ОБА склада — порядок блокировок фиксирован
            // (сначала меньший id, потом больший), чтобы исключить deadlock при
            // потенциальном встречном перемещении (документ A: склад 1 -> склад 2,
            // документ B: склад 2 -> склад 1, проводимые одновременно).
            if ($is_transfer) {
                $warehouse_ids_to_lock = [(int)$doc['warehouse_out_id'], (int)$doc['warehouse_in_id']];
                sort($warehouse_ids_to_lock);
                foreach ($warehouse_ids_to_lock as $wid) {
                    $stmt = fncQuery("SELECT id FROM objects WHERE id = :id FOR UPDATE", ['id' => $wid]);
                    if (!$stmt) {
                        throw new Exception('Не удалось заблокировать склад');
                    }
                }
                $warehouse_id = null;
            } else {
                $warehouse_id = (int)($is_outbound ? $doc['warehouse_out_id'] : $doc['warehouse_in_id']);
                $stmt = fncQuery("SELECT id FROM objects WHERE id = :id FOR UPDATE", ['id' => $warehouse_id]);
                if (!$stmt) {
                    throw new Exception('Не удалось заблокировать склад');
                }
            }

            $stmt = fncQuery(
                "SELECT id, nomenclature_id, quantity, price, sum
                 FROM document_items
                 WHERE document_id = :id
                 ORDER BY created_at",
                ['id' => $document_id]
            );
            if (!$stmt) {
                throw new Exception('Не удалось прочитать строки документа');
            }
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total_sum = 0;

            foreach ($items as $item) {
                if ($is_transfer) {
                    $item_sum = fncStockLedgerTransfer($document_id, $doc['document_type_id'], $item['id'], $doc['warehouse_out_id'], $doc['warehouse_in_id'], $item['nomenclature_id']);
                    if ($item_sum === false) {
                        throw new Exception('Не удалось переместить строку ' . $item['id']);
                    }
                    $total_sum += $item_sum;
                } elseif ($is_outbound) {
                    $item_sum = fncStockLedgerOut($document_id, $doc['document_type_id'], $item['id'], $warehouse_id, $item['nomenclature_id'], $item['quantity']);
                    if ($item_sum === false) {
                        throw new Exception('Не удалось создать запись остатка для строки ' . $item['id']);
                    }
                    $total_sum += $item_sum;
                } else {
                    $ok = fncStockLedgerIn($document_id, $doc['document_type_id'], $item['id'], $warehouse_id, $item['nomenclature_id'], $item['quantity'], $item['price'], $item['sum']);
                    if (!$ok) {
                        throw new Exception('Не удалось создать запись остатка для строки ' . $item['id']);
                    }
                    $total_sum += (float)$item['sum'];
                }
            }

            // Взаиморасчёты — только для incoming. Используем сумму ШАПКИ
            // (documents.sum), а не сумму строк — они могут законно не совпадать
            // (не все позиции документа поставщика заводятся построчно, см.
            // /decisions.md). Знак: мы получаем товар — минус (см. конвенцию:
            // платим/отгружаем — плюс, получаем — минус).
            if ($is_incoming) {
                $ok = fncSettlementsLedgerPost($document_id, $doc['organization_id'], $doc['counterparty_id'], -1 * (float)$doc['sum']);
                if ($ok === false) {
                    throw new Exception('Не удалось провести взаиморасчёты по документу');
                }
            }

            $update_fields = "status = 'posted', posting_date = NOW(), posting_finished_at = NOW(), posted_by = :posted_by";
            $update_binds = ['posted_by' => $user_id, 'id' => $document_id];

            if (!$is_incoming) {
                $update_fields .= ", sum = :sum";
                $update_binds['sum'] = $total_sum;
            }

            $stmt = fncQuery(
                "UPDATE documents SET {$update_fields} WHERE id = :id",
                $update_binds
            );
            if (!$stmt) {
                throw new Exception('Не удалось завершить проведение документа');
            }

            $pdo->commit();
            $result = ['sccss' => true];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            fncQuery(
                "UPDATE documents SET status = 'draft', posting_started_at = NULL WHERE id = :id",
                ['id' => $document_id]
            );
            fncLog('Ошибка проведения документа ' . $document_id . ': ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Не удалось провести документ']);
            exit;
        }
        break;

    //==========================================================================
    // Складские операции, связанные с конкретной строкой документа — для
    // просмотра после проведения. Строка может породить несколько записей
    // stock_ledger (Перемещение — по одной паре расход/приход на партию), и
    // каждая расходная запись может иметь расшифровку по партиям-источникам
    // через stock_ledger_consumption (Списание/расходная часть Перемещения).
    case 'document_item_ledger_data':
        $item_id = (int)($_POST['item_id'] ?? 0);

        $stmt = fncQuery(
            "SELECT di.id, di.quantity, n.name AS nomenclature_name, d.status, dt.code
             FROM document_items di
             LEFT JOIN nomenclature n ON n.id = di.nomenclature_id
             LEFT JOIN documents d ON d.id = di.document_id
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE di.id = :id",
            ['id' => $item_id]
        );
        $item_check = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$item_check) {
            echo json_encode(['sccss' => false, 'msg' => 'Строка не найдена']);
            exit;
        }
        if (!fncCan($perms, 'inventory.documents.' . $item_check['code'])) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        if ($item_check['status'] !== 'posted') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ ещё не проведён']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT sl.id, sl.warehouse_id, w.name AS warehouse_name,
                    sl.quantity, sl.price, sl.sum, sl.total_quantity, sl.source_ledger_id,
                    sw.name AS source_warehouse_name
             FROM stock_ledger sl
             LEFT JOIN objects w ON w.id = sl.warehouse_id
             LEFT JOIN stock_ledger sl_source ON sl_source.id = sl.source_ledger_id
             LEFT JOIN objects sw ON sw.id = sl_source.warehouse_id
             WHERE sl.document_item_id = :item_id
             ORDER BY sl.id",
            ['item_id' => $item_id]
        );
        $ledger_rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($ledger_rows as $key => $row) {
            $stmt = fncQuery(
                "SELECT slc.quantity, slc.price, slc.sum, sl_batch.date AS batch_date, d_batch.number AS batch_document_number, dt_batch.name AS batch_document_type_name
                 FROM stock_ledger_consumption slc
                 LEFT JOIN stock_ledger sl_batch ON sl_batch.id = slc.batch_ledger_id
                 LEFT JOIN documents d_batch ON d_batch.id = sl_batch.document_id
                 LEFT JOIN document_types dt_batch ON dt_batch.id = sl_batch.document_type_id
                 WHERE slc.ledger_id = :ledger_id
                 ORDER BY slc.id",
                ['ledger_id' => $row['id']]
            );
            $ledger_rows[$key]['consumption'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        $result = [
            'nomenclature_name' => $item_check['nomenclature_name'],
            'quantity'          => $item_check['quantity'],
            'ledger'            => $ledger_rows,
        ];
        break;
        
    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
