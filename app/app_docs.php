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

// Единая точность количеств в учёте — 3 знака (кг/л до грамма/миллилитра).
// Все колонки quantity/quantity_remaining/total_quantity — decimal(12,3);
// значения с клиента приводятся к ней здесь, а не округляются СУБД молча и
// по-разному в разных таблицах. QTY_EPS — допуск сравнения: половина
// последнего знака, т.е. разница в целую единицу 0.001 уже НЕ допускается.
const QTY_EPS = 0.0005;

function fncRoundQuantity($value) {
    return round((float)$value, 3);
}

// Денежное значение/цена с клиента: неотрицательное десятичное число в виде
// "123" или "123.45". Фронт отсекает минус и буквы, но не лишние точки
// ("1.2.3"), а прямой запрос в обход фронта не фильтруется вообще.
function fncIsNonNegativeNumber($value) {
    return (is_string($value) || is_int($value) || is_float($value))
        && preg_match('/^\d+(\.\d+)?$/', trim((string)$value)) === 1;
}

// Приводит quantity каждой партии ручного распределения к 3 знакам. Вызывается
// сразу после json_decode, до валидации и вставки — чтобы проверяли и писали
// одно и то же значение. Некорректные элементы оставляет как есть (их отсеет
// fncValidateManualBatches).
function fncNormalizeBatchQuantities(array $batches) {
    foreach ($batches as $i => $b) {
        if (is_array($b) && isset($b['quantity'])) {
            $batches[$i]['quantity'] = fncRoundQuantity($b['quantity']);
        }
    }
    return $batches;
}

// ── Доступ: права по типу документа + организации пользователя ──────────────
// Область организаций: null — без ограничения (полный доступ `inventory`,
// как и в форме создания документа), иначе список id организаций, где
// пользователь сейчас числится сотрудником (organization_staff, date_end IS
// NULL). Единственное место, где это правило записано — все actions ходят
// сюда, а не пишут своё условие.
function fncOrgScope() {
    global $user_id, $perms;
    static $scope = false;
    if ($scope !== false) {
        return $scope;
    }
    if (fncCan($perms, 'inventory')) {
        return $scope = null;
    }
    $stmt = fncQuery(
        "SELECT DISTINCT organization_id FROM organization_staff WHERE user_id = :user_id AND date_end IS NULL",
        ['user_id' => $user_id]
    );
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    return $scope = array_map('intval', $rows);
}

function fncCanAccessOrganization($organization_id) {
    $scope = fncOrgScope();
    return $scope === null || in_array((int)$organization_id, $scope, true);
}

// Доступ к документу: право на его тип (изменение — `inventory.documents.<код>`,
// просмотр — `.view`; fncCan поднимается по иерархии, поэтому право на
// изменение включает просмотр) И организация документа в области доступа
// пользователя. $row обязан содержать code и organization_id — при их
// отсутствии доступ закрыт (не открыт).
function fncDocAccess(array $row, $edit) {
    global $perms;
    if (empty($row['code'])) {
        return false;
    }
    return fncCan($perms, 'inventory.documents.' . $row['code'] . ($edit ? '' : '.view'))
        && fncCanAccessOrganization($row['organization_id'] ?? 0);
}

// Плейсхолдеры для IN (...) под список значений; пары имя=>значение
// добавляются в $binds.
function fncInPlaceholders(array $values, $prefix, array &$binds) {
    $keys = [];
    foreach (array_values($values) as $i => $value) {
        $key = $prefix . $i;
        $keys[] = ':' . $key;
        $binds[$key] = $value;
    }
    return implode(',', $keys);
}

// Условие " AND <колонка> IN (...)" по области организаций пользователя
// (пустая строка — без ограничения; " AND 1 = 0" — доступных организаций нет).
function fncOrgScopeSql($column, $prefix, array &$binds) {
    $scope = fncOrgScope();
    if ($scope === null) {
        return '';
    }
    if (empty($scope)) {
        return ' AND 1 = 0';
    }
    return " AND {$column} IN (" . fncInPlaceholders($scope, $prefix, $binds) . ")";
}

// Через сколько минут после начала проведения документ, застрявший в
// статусе posting, можно вернуть в черновик вручную. Совпадает с лимитом
// выполнения post_document (set_time_limit(600)): к этому моменту
// проводящий запрос гарантированно завершён, а незакоммиченная транзакция
// откатана СУБД — проводка атомарна, частичных записей в учёте нет.
const POSTING_RESET_MINUTES = 10;

// Состояние кнопки "Вернуть в черновик" для карточки документа.
function fncPostingResetState($status, $posting_minutes, $can_edit) {
    $is_posting = $status === 'posting';
    $minutes = $posting_minutes === null ? 0 : (int)$posting_minutes;
    return [
        'can_reset_posting'    => $is_posting && $can_edit && $minutes >= POSTING_RESET_MINUTES,
        'posting_minutes_left' => $is_posting ? max(0, POSTING_RESET_MINUTES - $minutes) : 0,
    ];
}

// Проверка ссылок документа при создании/смене шапки: организация в области
// доступа пользователя и является собственной активной; склады — активные
// складские объекты ИМЕННО этой организации; контрагент — активный
// контрагент. Клиентские списки это уже фильтруют, но сервер не должен
// верить присланным id. Возвращает null или текст ошибки.
function fncValidateDocumentRefs($organization_id, array $warehouse_ids, $counterparty_id) {
    if (!fncCanAccessOrganization($organization_id)) {
        return 'Нет доступа к выбранной организации';
    }

    $stmt = fncQuery(
        "SELECT id FROM organizations WHERE id = :id AND is_contractor = 0 AND is_active = 1",
        ['id' => $organization_id]
    );
    if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
        return 'Некорректная организация';
    }

    foreach (array_unique(array_filter($warehouse_ids)) as $warehouse_id) {
        $stmt = fncQuery(
            "SELECT id FROM objects WHERE id = :id AND organization_id = :organization_id AND is_stock = 1 AND is_active = 1",
            ['id' => $warehouse_id, 'organization_id' => $organization_id]
        );
        if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
            return 'Некорректный склад';
        }
    }

    if ($counterparty_id) {
        $stmt = fncQuery(
            "SELECT id FROM organizations WHERE id = :id AND is_contractor = 1 AND is_active = 1",
            ['id' => $counterparty_id]
        );
        if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
            return 'Некорректный контрагент';
        }
    }

    return null;
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

    return fncRoundQuantity($quantity) <= round($available - $already_reserved, 3) + QTY_EPS;
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
function fncStockLedgerIn($document_id, $document_type_id, $document_item_id, $warehouse_id, $nomenclature_id, $quantity, $price, $sum, $tax_sum = null) {
    $stmt = fncQuery(
        "SELECT id, total_quantity, total_sum
         FROM stock_ledger
         WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id AND is_last = 1
         LIMIT 1",
        ['nomenclature_id' => $nomenclature_id, 'warehouse_id' => $warehouse_id]
    );
    if (!$stmt) return false;
    $prev = $stmt->fetch(PDO::FETCH_ASSOC);

    $prev_quantity = $prev ? (float)$prev['total_quantity'] : 0;
    $prev_sum = $prev ? (float)$prev['total_sum'] : 0;

    $new_total_quantity = round($prev_quantity + (float)$quantity, 3);
    $new_total_sum = $prev_sum + (float)$sum;

    if ($prev) {
        $stmt = fncQuery("UPDATE stock_ledger SET is_last = 0 WHERE id = :id", ['id' => $prev['id']]);
        if (!$stmt) return false;
    }

    return fncQuery(
        "INSERT INTO stock_ledger
            (document_id, document_type_id, document_item_id, warehouse_id, nomenclature_id,
             date, quantity, price, sum, tax_sum, quantity_remaining, total_quantity, total_sum, is_last)
         VALUES
            (:document_id, :document_type_id, :document_item_id, :warehouse_id, :nomenclature_id,
             NOW(), :quantity, :price, :sum, :tax_sum, :quantity_remaining, :total_quantity, :total_sum, 1)",
        [
            'document_id'        => $document_id,
            'document_type_id'   => $document_type_id,
            'document_item_id'   => $document_item_id,
            'warehouse_id'       => $warehouse_id,
            'nomenclature_id'    => $nomenclature_id,
            'quantity'           => $quantity,
            'price'              => $price,
            'sum'                => $sum,
            'tax_sum'            => $tax_sum,
            'quantity_remaining' => $quantity,
            'total_quantity'     => $new_total_quantity,
            'total_sum'          => $new_total_sum,
        ]
    ) ? true : false;
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
    $exact_sum = 0;
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
        $exact_sum += $take_quantity * $batch_price;
    }

    // Цена — средневзвешенная по ТОЧНОЙ сумме (без промежуточного округления
    // до копеек по каждой партии), иначе при дробных количествах она уходит от
    // реальной цены партии (159,9399 вместо 159,96). Сумма строки ($total_sum)
    // остаётся суммой округлённых по партиям значений — она связывает строку с
    // расшифровкой в stock_ledger_consumption.
    $avg_price = (float)$quantity > 0 ? round($exact_sum / (float)$quantity, 4) : 0;

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

    $remaining_to_distribute = fncRoundQuantity($quantity);
    $distribution = [];

    foreach ($available_batches as $batch) {
        if ($remaining_to_distribute <= 0) break;

        $net_remaining = round((float)$batch['quantity_remaining'] - ($reservations[(int)$batch['id']] ?? 0), 3);
        if ($net_remaining <= 0) continue;

        $take = round(min($remaining_to_distribute, $net_remaining), 3);
        $distribution[] = ['batch_id' => (int)$batch['id'], 'quantity' => $take];
        $remaining_to_distribute = round($remaining_to_distribute - $take, 3);
    }

    if ($remaining_to_distribute > QTY_EPS) {
        return false;
    }

    return $distribution;
}

// Сколько из каждой партии уже занято ДРУГИМИ строками этого же документа —
// как обычными (document_item_batches), так и ингредиентами Выпуска ПФ
// (document_item_ingredient_batches, через document_item_ingredients). Обе
// таблицы — виртуальные резервы: партии физически резервируются в момент
// добавления строки-черновика, а списываются с quantity_remaining только при
// проведении, поэтому без объединения обеих таблиц строка обычного документа
// и строка ингредиента могли бы "выбрать" одну и ту же партию дважды —
// именно так и происходило до объединения этой функции. Возвращает map
// batch_ledger_id => количество. Гонка МЕЖДУ документами архитектурно
// исключена (один активный документ на склад), поэтому учитывать другие
// документы здесь не нужно.
function fncBatchReservationsInDocument($document_id, $exclude_item_id = null, $exclude_ingredient_id = null) {
    $exclude_sql_items = $exclude_item_id ? " AND di.id != :exclude_item_id" : "";
    $exclude_sql_ing = $exclude_ingredient_id ? " AND dii.id != :exclude_ingredient_id" : "";
    $binds = ['document_id' => $document_id, 'document_id2' => $document_id];
    if ($exclude_item_id) {
        $binds['exclude_item_id'] = $exclude_item_id;
    }
    if ($exclude_ingredient_id) {
        $binds['exclude_ingredient_id'] = $exclude_ingredient_id;
    }
    $stmt = fncQuery(
        "SELECT batch_ledger_id, SUM(quantity) AS reserved FROM (
            SELECT dib.batch_ledger_id, dib.quantity
            FROM document_item_batches dib
            INNER JOIN document_items di ON di.id = dib.document_item_id
            WHERE di.document_id = :document_id{$exclude_sql_items}
            UNION ALL
            SELECT diib.batch_ledger_id, diib.quantity
            FROM document_item_ingredient_batches diib
            INNER JOIN document_item_ingredients dii ON dii.id = diib.document_item_ingredient_id
            INNER JOIN document_items di ON di.id = dii.document_item_id
            WHERE di.document_id = :document_id2{$exclude_sql_ing}
         ) reservations
         GROUP BY batch_ledger_id",
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
        if (!isset($b['batch_id']) || !isset($b['quantity']) || fncRoundQuantity($b['quantity']) <= 0) {
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

        $net_remaining = round($remaining_by_batch[$batch_id] - ($reservations[$batch_id] ?? 0), 3);
        if (fncRoundQuantity($b['quantity']) > $net_remaining + QTY_EPS) {
            return 'Количество по партии превышает её остаток';
        }

        $batches_sum += fncRoundQuantity($b['quantity']);
    }

    if (abs(round($batches_sum, 3) - fncRoundQuantity($quantity)) > QTY_EPS) {
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
                TIMESTAMPDIFF(MINUTE, d.posting_started_at, NOW()) AS posting_minutes,
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
    $info['can_edit'] = fncDocAccess($info, true);
    $info = array_merge($info, fncPostingResetState($info['status'], $info['posting_minutes'], $info['can_edit']));
    $info = array_merge($info, fncUnpostCardState($info));

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
// плюс, мы получаем товар/деньги — минус). ОБЯЗАТЕЛЬНО вызывать внутри уже
// открытой транзакции: блокировка строки контрагента (organizations) держится
// до её commit/rollback — защита от гонки, когда два документа одного
// контрагента (на разных складах, иначе конфликтовали бы уже на уровне
// склада) проводятся одновременно. Именно так, а не GET_LOCK: именованный
// замок снимается раньше commit, и второй проводящий прочитал бы устаревший
// баланс. Чтение is_last-строки — тоже FOR UPDATE: обычный SELECT под
// REPEATABLE READ читает снимок, снятый ДО ожидания блокировки, а
// блокирующее чтение всегда видит последнюю закоммиченную версию.
// Возвращает true при успехе, false при ошибке.
function fncSettlementsLedgerPost($document_id, $organization_id, $counterparty_id, $delta_sum) {
    $stmt = fncQuery("SELECT id FROM organizations WHERE id = :id FOR UPDATE", ['id' => $counterparty_id]);
    if (!$stmt) return false;

    $stmt = fncQuery(
        "SELECT id, total_balance FROM settlements_ledger
         WHERE organization_id = :organization_id AND counterparty_id = :counterparty_id AND is_last = 1
         LIMIT 1
         FOR UPDATE",
        ['organization_id' => $organization_id, 'counterparty_id' => $counterparty_id]
    );
    if (!$stmt) return false;
    $prev = $stmt->fetch(PDO::FETCH_ASSOC);
    $prev_balance = $prev ? (float)$prev['total_balance'] : 0;

    $new_balance = round($prev_balance + (float)$delta_sum, 2);
    if (abs($new_balance) < 0.001) {
        $new_balance = 0;
    }

    if ($prev) {
        $stmt = fncQuery("UPDATE settlements_ledger SET is_last = 0 WHERE id = :id", ['id' => $prev['id']]);
        if (!$stmt) return false;
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
            "SELECT price, quantity_remaining, tax_sum, quantity AS batch_original_quantity
             FROM stock_ledger WHERE id = :id",
            ['id' => $d['batch_ledger_id']]
        );
        if (!$stmt) return false;
        $batch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) return false;

        $take_quantity = (float)$d['quantity'];
        $batch_price = (float)$batch['price'];
        $take_sum = round($take_quantity * $batch_price, 2);

        // НДС на единицу берём от ПЕРВОНАЧАЛЬНОГО количества этой партии
        // (quantity записи stock_ledger, не quantity_remaining) — tax_sum
        // в записи относится ко всему объёму партии при её создании,
        // не пересчитывается при частичных расходах/перемещениях.
        $tax_sum = null;
        if ($batch['tax_sum'] !== null && (float)$batch['batch_original_quantity'] != 0) {
            $tax_per_unit = (float)$batch['tax_sum'] / abs((float)$batch['batch_original_quantity']);
            $tax_sum = round($tax_per_unit * $take_quantity, 2);
        }

        $new_remaining = round((float)$batch['quantity_remaining'] - $take_quantity, 3);
        if (abs($new_remaining) < 0.001) {
            $new_remaining = 0;
        }

        $stmt = fncQuery(
            "UPDATE stock_ledger SET quantity_remaining = :remaining WHERE id = :id",
            ['remaining' => $new_remaining, 'id' => $d['batch_ledger_id']]
        );
        if (!$stmt) return false;

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

        $new_in_quantity = round($prev_in_quantity + $take_quantity, 3);
        $new_in_sum = $prev_in_sum + $take_sum;

        if ($prev_in) {
            $stmt = fncQuery("UPDATE stock_ledger SET is_last = 0 WHERE id = :id", ['id' => $prev_in['id']]);
            if (!$stmt) return false;
        }

        $stmt = fncQuery(
            "INSERT INTO stock_ledger
                (document_id, document_type_id, document_item_id, warehouse_id, nomenclature_id,
                 date, quantity, price, sum, tax_sum, quantity_remaining, total_quantity, total_sum, is_last, source_ledger_id)
             VALUES
                (:document_id, :document_type_id, :document_item_id, :warehouse_id, :nomenclature_id,
                 NOW(), :quantity, :price, :sum, :tax_sum, :quantity_remaining, :total_quantity, :total_sum, 1, :source_ledger_id)",
            [
                'document_id'        => $document_id,
                'document_type_id'   => $document_type_id,
                'document_item_id'   => $document_item_id,
                'warehouse_id'       => $warehouse_in_id,
                'nomenclature_id'    => $nomenclature_id,
                'quantity'           => $take_quantity,
                'price'              => $batch_price,
                'sum'                => $take_sum,
                'tax_sum'            => $tax_sum,
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
// Проверяет остаток ингредиента для Выпуска ПФ: доступный остаток минус
// уже зарезервированное количество ЭТОЙ ЖЕ номенклатуры другими строками
// ингредиентов в рамках того же документа (ингредиент может повторяться
// в разных ПФ одного документа выпуска). exclude_ingredient_id — исключить
// из подсчёта саму пересчитываемую строку (для будущего пересчёта при
// переключении авто/вручную).
function fncCheckIngredientAvailability($nomenclature_id, $warehouse_id, $document_id, $quantity, $exclude_ingredient_id = null) {
    $stmt = fncQuery(
        "SELECT total_quantity FROM stock_ledger
         WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id AND is_last = 1
         LIMIT 1",
        ['nomenclature_id' => $nomenclature_id, 'warehouse_id' => $warehouse_id]
    );
    $stock_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    $available = $stock_row ? (float)$stock_row['total_quantity'] : 0;

    $exclude_sql = $exclude_ingredient_id ? " AND dii.id != :exclude_id" : "";
    $binds = ['document_id' => $document_id, 'nomenclature_id' => $nomenclature_id];
    if ($exclude_ingredient_id) {
        $binds['exclude_id'] = $exclude_ingredient_id;
    }

    $stmt = fncQuery(
        "SELECT COALESCE(SUM(dii.quantity), 0) AS already_reserved
         FROM document_item_ingredients dii
         INNER JOIN document_items di ON di.id = dii.document_item_id
         WHERE di.document_id = :document_id AND dii.nomenclature_id = :nomenclature_id{$exclude_sql}",
        $binds
    );
    $already_reserved = $stmt ? (float)$stmt->fetch(PDO::FETCH_ASSOC)['already_reserved'] : 0;

    return fncRoundQuantity($quantity) <= round($available - $already_reserved, 3) + QTY_EPS;
}

// Отменяет текущее распределение ингредиента по партиям: удаляет записи
// document_item_ingredient_batches. Партии здесь — виртуальный резерв (как и
// у обычных строк через document_item_batches, см. fncBatchReservationsInDocument) —
// stock_ledger.quantity_remaining физически не трогается ни при резервировании,
// ни при его отмене, только при проведении документа (fncStockLedgerOutForIngredient).
// Используется при переключении режима авто/вручную и при пересохранении
// ручного распределения — оно всегда пересчитывается с нуля, а не
// корректируется частично.
function fncReleaseIngredientBatches($ingredient_id) {
    $stmt = fncQuery(
        "DELETE FROM document_item_ingredient_batches WHERE document_item_ingredient_id = :id",
        ['id' => $ingredient_id]
    );
    return $stmt ? true : false;
}
// Списание одного ингредиента Выпуска ПФ: та же механика, что и
// fncStockLedgerOut (одна сводная расходная запись, средневзвешенная цена,
// расшифровка по партиям через stock_ledger_consumption), но источник
// распределения — document_item_ingredient_batches (строка ингредиента),
// а не document_item_batches. Запись в stock_ledger получает
// document_item_id РОДИТЕЛЬСКОЙ строки ПФ (не строки ингредиента), чтобы
// просмотр складских операций строки показывал все её ингредиенты вместе.
// Возвращает сумму списания (float) при успехе, false при ошибке.
function fncStockLedgerOutForIngredient($document_id, $document_type_id, $pf_document_item_id, $ingredient_id, $warehouse_id, $nomenclature_id, $quantity) {
    global $pdo;

    $stmt = fncQuery(
        "SELECT id, batch_ledger_id, quantity FROM document_item_ingredient_batches WHERE document_item_ingredient_id = :id",
        ['id' => $ingredient_id]
    );
    if (!$stmt) return false;
    $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($distribution)) return false;

    $total_sum = 0;
    $exact_sum = 0;
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
        $exact_sum += $take_quantity * $batch_price;
    }

    // Цена — средневзвешенная по ТОЧНОЙ сумме (без промежуточного округления
    // до копеек по каждой партии), иначе при дробных количествах она уходит от
    // реальной цены партии (159,9399 вместо 159,96). Сумма строки ($total_sum)
    // остаётся суммой округлённых по партиям значений — она связывает строку с
    // расшифровкой в stock_ledger_consumption.
    $avg_price = (float)$quantity > 0 ? round($exact_sum / (float)$quantity, 4) : 0;

    $stmt = fncQuery(
        "SELECT id, total_quantity, total_sum
         FROM stock_ledger
         WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id AND is_last = 1
         LIMIT 1",
        ['nomenclature_id' => $nomenclature_id, 'warehouse_id' => $warehouse_id]
    );
    if (!$stmt) return false;
    $prev = $stmt->fetch(PDO::FETCH_ASSOC);

    $prev_quantity = $prev ? (float)$prev['total_quantity'] : 0;
    $prev_sum = $prev ? (float)$prev['total_sum'] : 0;

    $new_total_quantity = round($prev_quantity - (float)$quantity, 3);
    if (abs($new_total_quantity) < 0.001) {
        $new_total_quantity = 0;
    }

    $new_total_sum = round($prev_sum - $total_sum, 2);
    if (abs($new_total_sum) < 0.001) {
        $new_total_sum = 0;
    }

    if ($prev) {
        $stmt = fncQuery("UPDATE stock_ledger SET is_last = 0 WHERE id = :id", ['id' => $prev['id']]);
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
            'document_item_id' => $pf_document_item_id,
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

    return $total_sum;
}
// Откатывает ОДНУ строку stock_ledger: возвращает quantity_remaining партиям-
// источникам из её stock_ledger_consumption, удаляет сами consumption-строки
// и саму запись, затем восстанавливает is_last = 1 на предшествующей записи
// той же пары (nomenclature_id, warehouse_id). Предшественником может быть
// как чужая запись, так и другая строка ТОГО ЖЕ документа — для этого
// вызывающий код (unpost_document) обязан обрабатывать строки в порядке
// убывания id, тогда к моменту обработки каждой следующей строки её
// предшественник либо уже восстановлен, либо остался нетронутым чужим.
function fncUnwindStockLedgerRow($row) {
    $stmt = fncQuery(
        "SELECT batch_ledger_id, quantity FROM stock_ledger_consumption WHERE ledger_id = :id",
        ['id' => $row['id']]
    );
    if (!$stmt) return false;
    $consumptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($consumptions as $c) {
        $stmt = fncQuery(
            "UPDATE stock_ledger SET quantity_remaining = quantity_remaining + :qty WHERE id = :id",
            ['qty' => $c['quantity'], 'id' => $c['batch_ledger_id']]
        );
        if (!$stmt) return false;
    }

    $stmt = fncQuery("DELETE FROM stock_ledger_consumption WHERE ledger_id = :id", ['id' => $row['id']]);
    if (!$stmt) return false;

    $stmt = fncQuery("DELETE FROM stock_ledger WHERE id = :id", ['id' => $row['id']]);
    if (!$stmt) return false;

    $stmt = fncQuery(
        "SELECT id FROM stock_ledger
         WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id AND id < :id
         ORDER BY id DESC LIMIT 1",
        ['nomenclature_id' => $row['nomenclature_id'], 'warehouse_id' => $row['warehouse_id'], 'id' => $row['id']]
    );
    if (!$stmt) return false;
    $predecessor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($predecessor) {
        $stmt = fncQuery("UPDATE stock_ledger SET is_last = 1 WHERE id = :id", ['id' => $predecessor['id']]);
        if (!$stmt) return false;
    }

    return true;
}

// Типы документов, которые умеет проводить post_document и откатывать
// unpost_document.
const POSTABLE_CODES = ['stock_adjustment', 'writeoff', 'transfer', 'incoming_upd', 'incoming_waybill', 'incoming_receipt', 'sf_production', 'return_to_supplier', 'supplier_correction'];

// Можно ли сейчас отменить проведение документа. null — можно, иначе текст
// причины. Условия (подробно — над unpost_document):
//   C. на складах документа нет draft/posting-документов;
//   A. по каждой паре номенклатура+склад его записи stock_ledger — последние;
//   B. если есть контрагент — его запись settlements_ledger последняя.
// $for_update = true — вызов внутри транзакции отмены (контрагент блокируется
// FOR UPDATE); false — информационная проверка при открытии карточки, чтобы
// не показывать кнопку, которая заведомо не сработает. $ledger_rows и
// $settlement_row возвращаются для самой отмены. Сервер при отмене всё равно
// проверяет заново — карточка может устареть.
function fncUnpostBlockReason(array $doc, $for_update, &$ledger_rows = null, &$settlement_row = null) {
    $document_id   = (int)$doc['id'];
    $check_failed  = 'Не удалось проверить возможность отмены проведения';
    $warehouse_ids = array_unique(array_filter([(int)($doc['warehouse_out_id'] ?? 0), (int)($doc['warehouse_in_id'] ?? 0)]));
    if (empty($warehouse_ids)) {
        return $check_failed;
    }

    // Условие C. PDO без эмуляции подготовленных запросов (db.php) — один
    // именованный параметр дважды недопустим, поэтому отдельные плейсхолдеры
    // под склад списания и склад поступления.
    $binds = ['self_id' => $document_id];
    $wh_out_sql = fncInPlaceholders($warehouse_ids, 'whout', $binds);
    $wh_in_sql  = fncInPlaceholders($warehouse_ids, 'whin', $binds);
    $stmt = fncQuery(
        "SELECT COUNT(*) AS cnt FROM documents
         WHERE id != :self_id AND status IN ('draft', 'posting')
           AND (warehouse_out_id IN ({$wh_out_sql}) OR warehouse_in_id IN ({$wh_in_sql}))",
        $binds
    );
    if (!$stmt) {
        return $check_failed;
    }
    if ((int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] !== 0) {
        return 'На складе документа есть незавершённые документы — отмена невозможна';
    }

    // Условие A.
    $stmt = fncQuery(
        "SELECT id, nomenclature_id, warehouse_id FROM stock_ledger WHERE document_id = :id ORDER BY id DESC",
        ['id' => $document_id]
    );
    if (!$stmt) {
        return $check_failed;
    }
    $ledger_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($ledger_rows)) {
        return 'У документа нет записей учёта — нечего отменять';
    }

    $checked_pairs = [];
    foreach ($ledger_rows as $row) {
        $pair_key = $row['nomenclature_id'] . ':' . $row['warehouse_id'];
        if (isset($checked_pairs[$pair_key])) {
            continue;
        }
        $checked_pairs[$pair_key] = true;

        $stmt = fncQuery(
            "SELECT document_id FROM stock_ledger
             WHERE nomenclature_id = :nomenclature_id AND warehouse_id = :warehouse_id AND is_last = 1
             LIMIT 1",
            ['nomenclature_id' => $row['nomenclature_id'], 'warehouse_id' => $row['warehouse_id']]
        );
        if (!$stmt) {
            return $check_failed;
        }
        $last_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$last_row || (int)$last_row['document_id'] !== $document_id) {
            return 'После этого документа уже проводились другие операции по той же номенклатуре и складу — отмена невозможна';
        }
    }

    // Условие B.
    $settlement_row = null;
    if (!empty($doc['counterparty_id'])) {
        if ($for_update) {
            $stmt = fncQuery("SELECT id FROM organizations WHERE id = :id FOR UPDATE", ['id' => $doc['counterparty_id']]);
            if (!$stmt) {
                return $check_failed;
            }
        }

        $stmt = fncQuery("SELECT id FROM settlements_ledger WHERE document_id = :id LIMIT 1", ['id' => $document_id]);
        if (!$stmt) {
            return $check_failed;
        }
        $settlement_row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($settlement_row) {
            $stmt = fncQuery(
                "SELECT id FROM settlements_ledger
                 WHERE organization_id = :organization_id AND counterparty_id = :counterparty_id AND is_last = 1
                 LIMIT 1",
                ['organization_id' => $doc['organization_id'], 'counterparty_id' => $doc['counterparty_id']]
            );
            if (!$stmt) {
                return $check_failed;
            }
            $last_settlement = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$last_settlement || (int)$last_settlement['id'] !== (int)$settlement_row['id']) {
                return 'После этого документа уже проводились другие операции взаиморасчётов с этим контрагентом — отмена невозможна';
            }
        }
    }

    return null;
}

// Для карточки документа: может ли пользователь отменить проведение прямо
// сейчас. Только для проведённого документа поддерживаемого типа и только
// при праве на изменение; иначе проверку (несколько запросов) не делаем.
function fncUnpostCardState(array $info) {
    $state = ['can_unpost' => false, 'unpost_block_reason' => null];
    if ($info['status'] !== 'posted' || empty($info['can_edit']) || !in_array($info['code'], POSTABLE_CODES, true)) {
        return $state;
    }
    $reason = fncUnpostBlockReason($info, false);
    return ['can_unpost' => $reason === null, 'unpost_block_reason' => $reason];
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
        $all_document_types = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // Только типы, которые пользователь вправе хотя бы смотреть, и только
        // склады его организаций.
        $document_types = [];
        foreach ($all_document_types as $dt) {
            if (fncCan($perms, 'inventory.documents.' . $dt['code'] . '.view')) {
                $document_types[] = $dt;
            }
        }

        $binds = [];
        $scope_sql = fncOrgScopeSql('o.organization_id', 'org', $binds);
        $stmt = fncQuery(
            "SELECT o.id, o.name
             FROM objects o
             WHERE o.is_stock = 1 AND o.is_active = 1{$scope_sql}
             ORDER BY o.name",
            $binds
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

        $binds = [];
        $scope_sql = fncOrgScopeSql('o.id', 'org', $binds);
        $stmt = fncQuery(
            "SELECT o.id, o.name
             FROM organizations o
             WHERE o.is_contractor = 0 AND o.is_active = 1{$scope_sql}
               AND EXISTS (
                   SELECT 1 FROM objects ob
                   WHERE ob.organization_id = o.id AND ob.is_stock = 1 AND ob.is_active = 1
               )
             ORDER BY o.name",
            $binds
        );
        $organizations = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $binds = [];
        $scope_sql = fncOrgScopeSql('ob.organization_id', 'org', $binds);
        $stmt = fncQuery(
            "SELECT ob.id, ob.name, ob.organization_id
             FROM objects ob
             WHERE ob.is_stock = 1 AND ob.is_active = 1{$scope_sql}
             ORDER BY ob.name",
            $binds
        );
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

        $refs_error = fncValidateDocumentRefs($organization_id, [$warehouse_out_id, $warehouse_in_id], $counterparty_id);
        if ($refs_error !== null) {
            echo json_encode(['sccss' => false, 'msg' => $refs_error]);
            exit;
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

            if (!$number || !$document_date || !fncIsNonNegativeNumber($sum) || !fncIsNonNegativeNumber($tax_sum)) {
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
    // перед posted, внутри статуса — по created_at (новые сверху). Видны только
    // документы типов, на которые у пользователя есть право просмотра, и только
    // его организаций (fncOrgScope) — иначе список показал бы чужие документы.
    case 'documents_list':
        $date_from    = $_POST['date_from'] ?? '';
        $date_to      = $_POST['date_to'] ?? '';
        $type_code    = $_POST['type_code'] ?? '';
        $warehouse_id = (int)($_POST['warehouse_id'] ?? 0);

        if (!$date_from || !$date_to) {
            $result = [];
            break;
        }

        $stmt = fncQuery("SELECT code FROM document_types WHERE is_active = 1");
        $all_codes = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        $allowed_codes = [];
        foreach ($all_codes as $code) {
            if (fncCan($perms, 'inventory.documents.' . $code . '.view')) {
                $allowed_codes[] = $code;
            }
        }
        if (empty($allowed_codes) || ($type_code && !in_array($type_code, $allowed_codes, true))) {
            $result = [];
            break;
        }

        $where = ["d.document_date BETWEEN :date_from AND :date_to"];
        $binds = ['date_from' => $date_from, 'date_to' => $date_to];

        $where[] = "dt.code IN (" . fncInPlaceholders($allowed_codes, 'tc', $binds) . ")";
        $scope_sql = fncOrgScopeSql('d.organization_id', 'org', $binds);

        if ($type_code) {
            $where[] = "dt.code = :type_code";
            $binds['type_code'] = $type_code;
        }

        if ($warehouse_id) {
            $where[] = "(d.warehouse_out_id = :warehouse_id OR d.warehouse_in_id = :warehouse_id2)";
            $binds['warehouse_id']  = $warehouse_id;
            $binds['warehouse_id2'] = $warehouse_id;
        }

        $sql = "SELECT d.id, d.number, d.document_date, d.status, d.sum, d.tax_sum,
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
                WHERE " . implode(' AND ', $where) . $scope_sql . "
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
        if (!fncDocAccess($data['info'], false)) {
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
        if (!fncDocAccess($data['info'], true)) {
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
            "SELECT d.status, d.organization_id, dt.code, dt.direction
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
        if (!fncDocAccess($doc_check, false)) {
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
            'can_edit'    => fncDocAccess($doc_check, true),
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
            "SELECT d.id, d.status, d.organization_id, d.warehouse_out_id, d.warehouse_in_id,
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
        if (!fncDocAccess($doc, true)) {
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

            $refs_error = fncValidateDocumentRefs($organization_id, [$new_warehouse_out_id, $new_warehouse_in_id], $counterparty_id ?? null);
            if ($refs_error !== null) {
                echo json_encode(['sccss' => false, 'msg' => $refs_error]);
                exit;
            }

            if ($doc['direction'] === 'incoming') {
                $sum = fncValFind('sum', $params);
                $tax_sum = fncValFind('tax_sum', $params);
                if (!fncIsNonNegativeNumber($sum) || !fncIsNonNegativeNumber($tax_sum)) {
                    echo json_encode(['sccss' => false, 'msg' => 'Укажите корректные сумму и налог']);
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
    // — is_purchased ИЛИ is_produced (Выпуск ПФ сюда не попадает: его строки
    // создаёт new_production_item).
    // Партии (для складов списания без ручного ввода цены) — либо авто-FIFO,
    // либо явное ручное распределение с проверкой остатка каждой партии.
    case 'new_document_item':
        $document_id = (int)fncValFind('document_id', $params);
        if (!$document_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан документ']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.id, d.status, d.organization_id, d.warehouse_out_id, dt.code, dt.direction
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
        if (!fncDocAccess($doc, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        // Строки Выпуска ПФ несут ингредиенты и их партии — их создаёт только
        // new_production_item. Обычная строка без ингредиентов провелась бы как
        // ПФ с нулевой себестоимостью.
        if ($doc['code'] === 'sf_production') {
            echo json_encode(['sccss' => false, 'msg' => 'Строки Выпуска ПФ добавляются через форму выпуска']);
            exit;
        }

        $nomenclature_id = (int)fncValFind('nomenclature_id', $params);
        $quantity = fncRoundQuantity(fncValFind('quantity', $params));

        if (!$nomenclature_id || $quantity <= 0) {
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

            if (!fncIsNonNegativeNumber($price) || !fncIsNonNegativeNumber($sum)) {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
                exit;
            }
        }

        if ($doc['direction'] === 'incoming') {
            $tax_sum = fncValFind('tax_sum', $params);
            if (!fncIsNonNegativeNumber($tax_sum)) {
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
                $batches = fncNormalizeBatchQuantities($batches);
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

            if (in_array($doc['code'], ['return_to_supplier', 'supplier_correction'], true)) {
                // Возврат поставщику — особый случай: не одна строка с
                // отложенной ценой (как у Списания), а по ОДНОЙ строке
                // document_items НА КАЖДУЮ партию распределения, сразу с
                // подлинной ценой и НДС именно этой партии.
                // Цена/сумма/НДС строки известны уже на этапе создания — в
                // отличие от Списания, где price/sum заполняются только при
                // проведении документа.
                $created_ids = [];
                foreach ($batches as $b) {
                    $stmt = fncQuery(
                        "SELECT price, tax_sum, quantity AS batch_original_quantity
                         FROM stock_ledger WHERE id = :id",
                        ['id' => (int)$b['batch_id']]
                    );
                    $batch_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
                    if (!$batch_row) {
                        throw new Exception('Партия не найдена');
                    }

                    $row_quantity = (float)$b['quantity'];
                    $row_price = (float)$batch_row['price'];
                    $row_sum = round($row_quantity * $row_price, 2);

                    $row_tax_sum = null;
                    if ($batch_row['tax_sum'] !== null && (float)$batch_row['batch_original_quantity'] != 0) {
                        $tax_per_unit = (float)$batch_row['tax_sum'] / abs((float)$batch_row['batch_original_quantity']);
                        $row_tax_sum = round($tax_per_unit * $row_quantity, 2);
                    }

                    $stmt = fncQuery(
                        "INSERT INTO document_items (document_id, nomenclature_id, quantity, price, sum, tax_sum, created_by)
                         VALUES (:document_id, :nomenclature_id, :quantity, :price, :sum, :tax_sum, :created_by)",
                        [
                            'document_id'     => $document_id,
                            'nomenclature_id' => $nomenclature_id,
                            'quantity'        => $row_quantity,
                            'price'           => $row_price,
                            'sum'             => $row_sum,
                            'tax_sum'         => $row_tax_sum,
                            'created_by'      => $user_id,
                        ]
                    );
                    if (!$stmt) {
                        throw new Exception('Не удалось создать строку документа');
                    }
                    $new_item_id = (int)$pdo->lastInsertId();

                    $stmt = fncQuery(
                        "INSERT INTO document_item_batches (document_item_id, batch_ledger_id, quantity)
                         VALUES (:document_item_id, :batch_ledger_id, :quantity)",
                        [
                            'document_item_id' => $new_item_id,
                            'batch_ledger_id'  => (int)$b['batch_id'],
                            'quantity'         => $row_quantity,
                        ]
                    );
                    if (!$stmt) {
                        throw new Exception('Не удалось сохранить распределение по партиям');
                    }

                    $created_ids[] = $new_item_id;
                }

                $pdo->commit();
                $result = ['sccss' => true, 'ids' => $created_ids];

            } else {
                // Все остальные типы — прежняя логика: одна строка документа,
                // при необходимости с распределением по нескольким партиям
                // внутри неё (Списание/Перемещение).
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
            }

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
                    d.tax_sum AS document_tax_sum, d.warehouse_out_id, d.organization_id, dt.code, dt.direction
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
        if (!fncDocAccess($item, true)) {
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
            "SELECT d.warehouse_out_id, d.organization_id, dt.code
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
        if (!fncDocAccess($doc, true)) {
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
            "SELECT di.id, di.document_id, di.nomenclature_id, d.status, d.organization_id, d.warehouse_out_id, dt.code, dt.direction
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
        if (!fncDocAccess($item, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $has_batches = $item['warehouse_out_id'] && !fncItemRequiresPrice($item['direction'], $item['code']);
        if ($has_batches) {
            echo json_encode(['sccss' => false, 'msg' => 'Редактирование строки с распределением по партиям недоступно, удалите и создайте заново']);
            exit;
        }

        $quantity = fncRoundQuantity(fncValFind('quantity', $params));
        if ($quantity <= 0) {
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

            if (!fncIsNonNegativeNumber($price) || !fncIsNonNegativeNumber($sum)) {
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
            if (!fncIsNonNegativeNumber($tax_sum)) {
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
            "SELECT di.id, d.status, d.organization_id, dt.code
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
        if (!fncDocAccess($item, true)) {
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
            "SELECT d.id, d.status, d.organization_id, dt.code
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
        if (!fncDocAccess($doc, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $stmt = fncQuery("SELECT COUNT(*) AS cnt FROM document_items WHERE document_id = :id", ['id' => $id]);
        $items_count = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] : 0;
        if ($items_count > 0) {
            echo json_encode(['sccss' => false, 'msg' => 'Сначала удалите все строки документа']);
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
    // Типы, которые проводятся, перечислены в POSTABLE_CODES: приход
    // (fncStockLedgerIn), расход по партиям (fncStockLedgerOut), перемещение
    // (fncStockLedgerTransfer), выпуск ПФ (ингредиенты + приход ПФ). Если
    // проведение оборвалось (документ завис в posting), его возвращает в
    // черновик вручную reset_posting — только через POSTING_RESET_MINUTES.
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
        if (!fncDocAccess($doc, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        if (!in_array($doc['code'], POSTABLE_CODES, true)) {
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

            $is_incoming    = $doc['direction'] === 'incoming';
            $is_outbound    = in_array($doc['code'], ['writeoff', 'return_to_supplier', 'supplier_correction'], true);
            $is_transfer    = $doc['code'] === 'transfer';
            $is_production  = $doc['code'] === 'sf_production';
            $is_return      = in_array($doc['code'], ['return_to_supplier', 'supplier_correction'], true);

            $total_sum = 0;
            $total_tax_sum = 0;

            if ($is_production) {
                // Выпуск ПФ: единственный склад (warehouse_in_id == warehouse_out_id,
                // заложено при создании документа). Для каждой строки ПФ — списание
                // всех её ингредиентов, расчёт фактической себестоимости, оприходование
                // ПФ с этой ценой, проверка отклонения от цены последнего выпуска.
                $confirm_override = fncValFind('confirm_override', $params);
                $warehouse_id = (int)$doc['warehouse_in_id'];

                $stmt = fncQuery("SELECT id FROM objects WHERE id = :id FOR UPDATE", ['id' => $warehouse_id]);
                if (!$stmt) {
                    throw new Exception('Не удалось заблокировать склад');
                }

                $stmt = fncQuery(
                    "SELECT id, nomenclature_id, quantity FROM document_items WHERE document_id = :id ORDER BY created_at",
                    ['id' => $document_id]
                );
                if (!$stmt) {
                    throw new Exception('Не удалось прочитать строки ПФ');
                }
                $pf_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($pf_items as $pf) {
                    $stmt = fncQuery(
                        "SELECT id, nomenclature_id, quantity FROM document_item_ingredients WHERE document_item_id = :id",
                        ['id' => $pf['id']]
                    );
                    if (!$stmt) {
                        throw new Exception('Не удалось прочитать ингредиенты строки ' . $pf['id']);
                    }
                    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $pf_cost = 0;
                    foreach ($ingredients as $ing) {
                        $ing_sum = fncStockLedgerOutForIngredient($document_id, $doc['document_type_id'], $pf['id'], $ing['id'], $warehouse_id, $ing['nomenclature_id'], $ing['quantity']);
                        if ($ing_sum === false) {
                            throw new Exception('Не удалось списать ингредиент ' . $ing['id']);
                        }
                        $pf_cost += $ing_sum;
                    }

                    $pf_price = (float)$pf['quantity'] > 0 ? round($pf_cost / (float)$pf['quantity'], 4) : 0;

                    $stmt = fncQuery(
                        "SELECT sl.price FROM stock_ledger sl
                         WHERE sl.nomenclature_id = :nomenclature_id AND sl.document_type_id = :document_type_id
                           AND sl.warehouse_id = :warehouse_id AND sl.quantity > 0
                         ORDER BY sl.id DESC LIMIT 1",
                        ['nomenclature_id' => $pf['nomenclature_id'], 'document_type_id' => $doc['document_type_id'], 'warehouse_id' => $warehouse_id]
                    );
                    $last_row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

                    if ($last_row && (float)$last_row['price'] > 0) {
                        $last_price = (float)$last_row['price'];
                        $deviation = abs($pf_price - $last_price) / $last_price * 100;

                        if ($deviation > 10) {
                            $has_override = fncCan($perms, 'inventory');
                            if (!$has_override) {
                                $pdo->rollBack();
                                fncQuery(
                                    "UPDATE documents SET status = 'draft', posting_started_at = NULL WHERE id = :id",
                                    ['id' => $document_id]
                                );
                                echo json_encode([
                                    'sccss' => false,
                                    'msg' => 'Отклонение цены выпуска составляет ' . round($deviation, 1) . '% (допустимо до 10%). Провести такой документ может только пользователь с полным доступом к товароучёту.',
                                ]);
                                exit;
                            }
                            if (!$confirm_override) {
                                $pdo->rollBack();
                                fncQuery(
                                    "UPDATE documents SET status = 'draft', posting_started_at = NULL WHERE id = :id",
                                    ['id' => $document_id]
                                );
                                echo json_encode([
                                    'sccss' => false,
                                    'needs_confirmation' => true,
                                    'deviation' => round($deviation, 1),
                                    'msg' => 'Отклонение цены выпуска составляет ' . round($deviation, 1) . '%. Требуется подтверждение.',
                                ]);
                                exit;
                            }
                        }
                    }

                    $stmt = fncQuery(
                        "UPDATE document_items SET price = :price, sum = :sum WHERE id = :id",
                        ['price' => $pf_price, 'sum' => $pf_cost, 'id' => $pf['id']]
                    );
                    if (!$stmt) {
                        throw new Exception('Не удалось обновить строку ПФ ' . $pf['id']);
                    }

                    $ok = fncStockLedgerIn($document_id, $doc['document_type_id'], $pf['id'], $warehouse_id, $pf['nomenclature_id'], $pf['quantity'], $pf_price, $pf_cost);
                    if (!$ok) {
                        throw new Exception('Не удалось оприходовать ПФ ' . $pf['id']);
                    }

                    $total_sum += $pf_cost;
                }

            } else {
                // Все остальные типы — прежняя логика.
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
                    "SELECT id, nomenclature_id, quantity, price, sum, tax_sum
                     FROM document_items
                     WHERE document_id = :id
                     ORDER BY created_at",
                    ['id' => $document_id]
                );
                if (!$stmt) {
                    throw new Exception('Не удалось прочитать строки документа');
                }
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($items as $item) {
                    if ($is_transfer) {
                        $item_sum = fncStockLedgerTransfer($document_id, $doc['document_type_id'], $item['id'], $doc['warehouse_out_id'], $doc['warehouse_in_id'], $item['nomenclature_id']);
                        if ($item_sum === false) {
                            throw new Exception('Не удалось переместить строку ' . $item['id']);
                        }
                        $total_sum += $item_sum;
                    } elseif ($is_outbound) {
                        // writeoff и return_to_supplier — расход со склада по
                        // уже сохранённому распределению document_item_batches.
                        // Для return_to_supplier в этом распределении всегда
                        // ровно одна партия на строку (см. new_document_item),
                        // поэтому средневзвешенная цена внутри fncStockLedgerOut
                        // вырождается в подлинную цену этой партии — пересчёт
                        // идемпотентен, tax_sum строки функция не трогает.
                        $item_sum = fncStockLedgerOut($document_id, $doc['document_type_id'], $item['id'], $warehouse_id, $item['nomenclature_id'], $item['quantity']);
                        if ($item_sum === false) {
                            throw new Exception('Не удалось создать запись остатка для строки ' . $item['id']);
                        }
                        $total_sum += $item_sum;
                    } else {
                        // tax_sum передаётся дальше в fncStockLedgerIn только
                        // здесь — единственная ветка, где document_items.tax_sum
                        // заполнен (incoming). Для stock_adjustment колонка
                        // всегда NULL, fncStockLedgerIn это корректно обработает.
                        $ok = fncStockLedgerIn($document_id, $doc['document_type_id'], $item['id'], $warehouse_id, $item['nomenclature_id'], $item['quantity'], $item['price'], $item['sum'], $item['tax_sum']);
                        if (!$ok) {
                            throw new Exception('Не удалось создать запись остатка для строки ' . $item['id']);
                        }
                        $total_sum += (float)$item['sum'];
                    }
                    // Накопление НДС по строкам — для writeoff/transfer/sf_production
                    // item['tax_sum'] всегда NULL, вклад 0, ничего не меняется.
                    // Для return_to_supplier здесь появляется подлинная сумма НДС
                    // возврата, которую дальше пишем в шапку документа.
                    $total_tax_sum += (float)($item['tax_sum'] ?? 0);
                }
            }

            // Взаиморасчёты. Incoming — мы получаем товар, минус, по сумме
            // ШАПКИ (documents.sum — может законно не совпадать с суммой
            // строк — это допустимо, сверка идёт на стороне интерфейса).
            // Return_to_supplier — мы отгружаем
            // товар поставщику, плюс, по факту посчитанной суммы строк
            // ($total_sum) — у outgoing-документов сумма шапки не вводится
            // вручную и известна только по результату проведения, как у
            // writeoff.
            if ($is_incoming) {
                $ok = fncSettlementsLedgerPost($document_id, $doc['organization_id'], $doc['counterparty_id'], -1 * (float)$doc['sum']);
                if ($ok === false) {
                    throw new Exception('Не удалось провести взаиморасчёты по документу');
                }
            } elseif ($is_return) {
                $ok = fncSettlementsLedgerPost($document_id, $doc['organization_id'], $doc['counterparty_id'], (float)$total_sum);
                if ($ok === false) {
                    throw new Exception('Не удалось провести взаиморасчёты по документу');
                }
            }

            $update_fields = "status = 'posted', posting_date = NOW(), posting_finished_at = NOW(), posted_by = :posted_by";
            $update_binds = ['posted_by' => $user_id, 'id' => $document_id];

            if (!$is_incoming) {
                $update_fields .= ", sum = :sum, tax_sum = :tax_sum";
                $update_binds['sum'] = $total_sum;
                $update_binds['tax_sum'] = $total_tax_sum;
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
    // Возврат зависшего документа из posting в черновик. Только вручную и
    // только через POSTING_RESET_MINUTES после начала проведения (см. константу:
    // к этому моменту проводящий запрос завершён, а транзакция откатана).
    // Автоматически ничего не возвращается — решение принимает человек. Если по
    // документу уже есть записи учёта (при атомарной проводке невозможно),
    // возврат отклоняется: такой случай нужно разбирать вручную.
    case 'reset_posting':
        $document_id = (int)fncValFind('id', $params);
        if (!$document_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан документ']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.id, d.status, d.organization_id,
                    TIMESTAMPDIFF(MINUTE, d.posting_started_at, NOW()) AS posting_minutes,
                    dt.code
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
        if (!fncDocAccess($doc, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        if ($doc['status'] !== 'posting') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не находится в проведении']);
            exit;
        }

        $reset_state = fncPostingResetState($doc['status'], $doc['posting_minutes'], true);
        if (!$reset_state['can_reset_posting']) {
            echo json_encode([
                'sccss' => false,
                'msg'   => 'Вернуть в черновик можно через ' . $reset_state['posting_minutes_left'] . ' мин. — проведение ещё может выполняться',
            ]);
            exit;
        }

        $stmt = fncQuery(
            "SELECT (SELECT COUNT(*) FROM stock_ledger WHERE document_id = :id1)
                  + (SELECT COUNT(*) FROM settlements_ledger WHERE document_id = :id2) AS cnt",
            ['id1' => $document_id, 'id2' => $document_id]
        );
        $ledger_rows = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] : -1;
        if ($ledger_rows !== 0) {
            fncLog('Отказ возврата документа ' . $document_id . ' из posting: записей учёта ' . $ledger_rows);
            echo json_encode(['sccss' => false, 'msg' => 'По документу уже есть записи учёта — вернуть в черновик нельзя, обратитесь к администратору']);
            exit;
        }

        $stmt = fncQuery(
            "UPDATE documents SET status = 'draft', posting_started_at = NULL
             WHERE id = :id AND status = 'posting'
               AND TIMESTAMPDIFF(MINUTE, posting_started_at, NOW()) >= " . (int)POSTING_RESET_MINUTES,
            ['id' => $document_id]
        );
        if (!$stmt || $stmt->rowCount() === 0) {
            echo json_encode(['sccss' => false, 'msg' => 'Не удалось вернуть документ в черновик']);
            exit;
        }

        fncLog('Документ ' . $document_id . ' возвращён из posting в draft пользователем ' . $user_id);
        $result = ['sccss' => true];
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
            "SELECT di.id, di.quantity, n.name AS nomenclature_name, d.status, d.organization_id, dt.code
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
        if (!fncDocAccess($item_check, false)) {
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

    //==========================================================================
    // Состав ПФ для офканваса выпуска: список ингредиентов (nomenclature_composition),
    // для каждого — остаток на складе, уже зарезервированное количество другими
    // строками ПФ этого же документа (ингредиент может повторяться в разных ПФ
    // одного документа выпуска), и доступный остаток. is_blocked — остатка нет
    // вообще, ввод для этого ингредиента невозможен, выпуск этой строки не
    // может быть сохранён, пока ингредиент не появится на складе.
    case 'production_composition_data':
        $document_id = (int)($_POST['document_id'] ?? 0);
        $nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);

        if (!$document_id || !$nomenclature_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указаны параметры']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.warehouse_in_id, d.status, d.organization_id, dt.code
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
        if (!fncDocAccess($doc, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        $warehouse_id = (int)$doc['warehouse_in_id'];

        $stmt = fncQuery(
            "SELECT nc.ingredient_id AS nomenclature_id, n.name AS nomenclature_name, u.short_name AS unit_short_name,
                    (SELECT sl.total_quantity FROM stock_ledger sl
                     WHERE sl.nomenclature_id = nc.ingredient_id AND sl.warehouse_id = :warehouse_id AND sl.is_last = 1
                     LIMIT 1) AS stock_quantity,
                    (SELECT COALESCE(SUM(dii.quantity), 0)
                     FROM document_item_ingredients dii
                     INNER JOIN document_items di ON di.id = dii.document_item_id
                     WHERE di.document_id = :document_id AND dii.nomenclature_id = nc.ingredient_id) AS already_reserved
             FROM nomenclature_composition nc
             LEFT JOIN nomenclature n ON n.id = nc.ingredient_id
             LEFT JOIN units u ON u.id = n.unit_id
             WHERE nc.nomenclature_id = :nomenclature_id",
            ['warehouse_id' => $warehouse_id, 'document_id' => $document_id, 'nomenclature_id' => $nomenclature_id]
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rows as $key => $row) {
            $stock = $row['stock_quantity'] !== null ? (float)$row['stock_quantity'] : 0;
            $reserved = (float)$row['already_reserved'];
            $available = round($stock - $reserved, 3);
            $rows[$key]['available'] = $available;
            $rows[$key]['is_blocked'] = $available <= 0;
        }

        $result = ['ingredients' => $rows];
        break;

    //==========================================================================
    // Сохранение строки Выпуска ПФ: создаёт строку document_items (сам ПФ,
    // price/sum пока NULL — появятся при проведении) и по каждому ингредиенту
    // состава — строку document_item_ingredients с НЕМЕДЛЕННЫМ авто-FIFO
    // распределением по партиям (document_item_ingredient_batches). Ручной
    // режим распределения выбирается постфактум, уже для сохранённой строки
    // (см. upd_ingredient_batch_mode) — на этом шаге всегда авто. Состав
    // подгружается из nomenclature_composition целиком и обязателен весь —
    // частичное заполнение не допускается.
    case 'new_production_item':
        $document_id = (int)fncValFind('document_id', $params);
        if (!$document_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан документ']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.id, d.status, d.organization_id, d.warehouse_in_id, dt.code
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
        if ($doc['code'] !== 'sf_production') {
            echo json_encode(['sccss' => false, 'msg' => 'Некорректный тип документа']);
            exit;
        }
        if ($doc['status'] !== 'draft') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ уже проведён']);
            exit;
        }
        if (!fncDocAccess($doc, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $nomenclature_id = (int)fncValFind('nomenclature_id', $params);
        $produced_quantity = fncRoundQuantity(fncValFind('produced_quantity', $params));
        $ingredients_raw = fncValFind('ingredients', $params);

        if (!$nomenclature_id || $produced_quantity <= 0) {
            echo json_encode(['sccss' => false, 'msg' => 'Заполните обязательные поля']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT id FROM nomenclature WHERE id = :id AND is_produced = 1 AND is_active = 1",
            ['id' => $nomenclature_id]
        );
        if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['sccss' => false, 'msg' => 'Некорректная номенклатура']);
            exit;
        }

        $ingredients = $ingredients_raw ? json_decode($ingredients_raw, true) : null;
        if (!is_array($ingredients) || empty($ingredients)) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан состав ингредиентов']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT ingredient_id FROM nomenclature_composition WHERE nomenclature_id = :id",
            ['id' => $nomenclature_id]
        );
        $composition_rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $composition_ids = array_map(fn($r) => (int)$r['ingredient_id'], $composition_rows);

        if (empty($composition_ids)) {
            echo json_encode(['sccss' => false, 'msg' => 'У выбранного ПФ не задан состав']);
            exit;
        }

        // Все ингредиенты состава обязательны — сверяем присланный набор с
        // составом ровно один в один, без пропусков и без лишних позиций.
        $sent_ids = array_map(fn($i) => (int)$i['nomenclature_id'], $ingredients);
        sort($composition_ids);
        sort($sent_ids);
        if ($composition_ids !== $sent_ids) {
            echo json_encode(['sccss' => false, 'msg' => 'Должны быть заполнены все ингредиенты состава']);
            exit;
        }

        $warehouse_id = (int)$doc['warehouse_in_id'];

        foreach ($ingredients as $k => $ing) {
            $qty = fncRoundQuantity($ing['quantity'] ?? 0);
            if ($qty <= 0) {
                echo json_encode(['sccss' => false, 'msg' => 'Заполните количество по всем ингредиентам']);
                exit;
            }
            $ingredients[$k]['quantity'] = $qty;
            if (!fncCheckIngredientAvailability((int)$ing['nomenclature_id'], $warehouse_id, $document_id, $qty)) {
                echo json_encode(['sccss' => false, 'msg' => 'Количество одного из ингредиентов превышает доступный остаток']);
                exit;
            }
        }

        global $pdo;

        try {
            $pdo->beginTransaction();

            $stmt = fncQuery(
                "INSERT INTO document_items (document_id, nomenclature_id, quantity, price, sum, tax_sum, created_by)
                 VALUES (:document_id, :nomenclature_id, :quantity, NULL, NULL, NULL, :created_by)",
                [
                    'document_id'     => $document_id,
                    'nomenclature_id' => $nomenclature_id,
                    'quantity'        => $produced_quantity,
                    'created_by'      => $user_id,
                ]
            );
            if (!$stmt) {
                throw new Exception('Не удалось создать строку ПФ');
            }
            $pf_item_id = (int)$pdo->lastInsertId();

            foreach ($ingredients as $ing) {
                $ing_nomenclature_id = (int)$ing['nomenclature_id'];
                $ing_quantity = $ing['quantity'];

                $stmt = fncQuery(
                    "INSERT INTO document_item_ingredients (document_item_id, nomenclature_id, quantity, created_by)
                     VALUES (:document_item_id, :nomenclature_id, :quantity, :created_by)",
                    [
                        'document_item_id' => $pf_item_id,
                        'nomenclature_id'  => $ing_nomenclature_id,
                        'quantity'         => $ing_quantity,
                        'created_by'       => $user_id,
                    ]
                );
                if (!$stmt) {
                    throw new Exception('Не удалось сохранить ингредиент');
                }
                $ingredient_row_id = (int)$pdo->lastInsertId();

                $distribution = fncAutoDistributeBatches($ing_nomenclature_id, $warehouse_id, $ing_quantity, $document_id);
                if ($distribution === false) {
                    throw new Exception('Недостаточно партий для автоматического распределения ингредиента ' . $ing_nomenclature_id);
                }

                foreach ($distribution as $b) {
                    $stmt = fncQuery(
                        "INSERT INTO document_item_ingredient_batches (document_item_ingredient_id, batch_ledger_id, quantity)
                         VALUES (:ingredient_id, :batch_ledger_id, :quantity)",
                        [
                            'ingredient_id'   => $ingredient_row_id,
                            'batch_ledger_id' => (int)$b['batch_id'],
                            'quantity'        => $b['quantity'],
                        ]
                    );
                    if (!$stmt) {
                        throw new Exception('Не удалось сохранить распределение партий ингредиента');
                    }
                }
            }

            $pdo->commit();
            $result = ['sccss' => true, 'id' => $pf_item_id];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            fncLog('Ошибка сохранения строки Выпуска ПФ: ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Не удалось сохранить строку']);
            exit;
        }
        break;

    //==========================================================================
    // Строки документа Выпуска ПФ с вложенными ингредиентами — для отображения
    // в карточке. Каждая строка ПФ (document_items) сопровождается списком её
    // ингредиентов (document_item_ingredients) с текущим batch_mode и статусом
    // распределения (для ручного режима — распределено ли уже через партии).
    case 'production_items_data':
        $id = (int)($_POST['id'] ?? 0);

        $stmt = fncQuery(
            "SELECT d.status, d.organization_id, dt.code
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
        if (!fncDocAccess($doc_check, false)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT di.id, di.nomenclature_id, n.name AS nomenclature_name, di.quantity, di.price, di.sum,
                    u.short_name AS unit_short_name
             FROM document_items di
             LEFT JOIN nomenclature n ON n.id = di.nomenclature_id
             LEFT JOIN units u ON u.id = n.unit_id
             WHERE di.document_id = :id
             ORDER BY di.created_at",
            ['id' => $id]
        );
        $pf_items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $total_sum = 0;
        foreach ($pf_items as $key => $pf) {
            $stmt = fncQuery(
                "SELECT dii.id, dii.nomenclature_id, n.name AS nomenclature_name, dii.quantity, dii.batch_mode,
                        u.short_name AS unit_short_name,
                        (SELECT COALESCE(SUM(diib.quantity), 0)
                         FROM document_item_ingredient_batches diib
                         WHERE diib.document_item_ingredient_id = dii.id) AS distributed_quantity
                 FROM document_item_ingredients dii
                 LEFT JOIN nomenclature n ON n.id = dii.nomenclature_id
                 LEFT JOIN units u ON u.id = n.unit_id
                 WHERE dii.document_item_id = :document_item_id
                 ORDER BY dii.created_at",
                ['document_item_id' => $pf['id']]
            );
            $ingredients = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            foreach ($ingredients as $ikey => $ing) {
                $ingredients[$ikey]['is_distributed'] = abs((float)$ing['distributed_quantity'] - (float)$ing['quantity']) < QTY_EPS;
            }

            $pf_items[$key]['ingredients'] = $ingredients;
            $total_sum += (float)$pf['sum'];
        }

        $result = [
            'items'     => $pf_items,
            'status'    => $doc_check['status'],
            'has_items' => !empty($pf_items),
            'total_sum' => $total_sum,
            'can_edit'  => fncDocAccess($doc_check, true),
        ];
        break;

    //==========================================================================
    // Переключение режима распределения партий ингредиента. Смена режима
    // ВСЕГДА пересчитывает распределение с нуля: сначала освобождаются старые
    // партии (fncReleaseIngredientBatches), затем — при переходе на auto —
    // сразу выполняется новое авто-распределение; при переходе на manual
    // распределение остаётся пустым до отдельного сохранения через
    // save_ingredient_batches (пользователь ещё должен выбрать партии).
    case 'upd_ingredient_batch_mode':
        $ingredient_id = (int)fncValFind('ingredient_id', $params);
        $mode = fncValFind('mode', $params);

        if (!$ingredient_id || !in_array($mode, ['auto', 'manual'], true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Некорректные параметры']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT dii.id, dii.nomenclature_id, dii.quantity, di.document_id, d.status, d.organization_id, d.warehouse_in_id, dt.code
             FROM document_item_ingredients dii
             LEFT JOIN document_items di ON di.id = dii.document_item_id
             LEFT JOIN documents d ON d.id = di.document_id
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE dii.id = :id",
            ['id' => $ingredient_id]
        );
        $ing = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$ing) {
            echo json_encode(['sccss' => false, 'msg' => 'Ингредиент не найден']);
            exit;
        }
        if ($ing['status'] !== 'draft') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ уже проведён']);
            exit;
        }
        if (!fncDocAccess($ing, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        global $pdo;

        try {
            $pdo->beginTransaction();

            if (!fncReleaseIngredientBatches($ingredient_id)) {
                throw new Exception('Не удалось освободить прежнее распределение');
            }

            if ($mode === 'auto') {
                $distribution = fncAutoDistributeBatches($ing['nomenclature_id'], $ing['warehouse_in_id'], $ing['quantity'], $ing['document_id']);
                if ($distribution === false) {
                    throw new Exception('Недостаточно партий для автоматического распределения');
                }
                foreach ($distribution as $b) {
                    $stmt = fncQuery(
                        "INSERT INTO document_item_ingredient_batches (document_item_ingredient_id, batch_ledger_id, quantity)
                         VALUES (:ingredient_id, :batch_ledger_id, :quantity)",
                        ['ingredient_id' => $ingredient_id, 'batch_ledger_id' => (int)$b['batch_id'], 'quantity' => $b['quantity']]
                    );
                    if (!$stmt) {
                        throw new Exception('Не удалось сохранить распределение');
                    }
                }
            }

            $stmt = fncQuery(
                "UPDATE document_item_ingredients SET batch_mode = :mode WHERE id = :id",
                ['mode' => $mode, 'id' => $ingredient_id]
            );
            if (!$stmt) {
                throw new Exception('Не удалось обновить режим распределения');
            }

            $pdo->commit();
            $result = ['sccss' => true];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            fncLog('Ошибка переключения режима распределения ингредиента: ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Не удалось переключить режим']);
            exit;
        }
        break;

    case 'ingredient_batches_data':
        $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
        if (!$ingredient_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан ингредиент']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT dii.nomenclature_id, di.document_id, d.organization_id, d.warehouse_in_id, dt.code
             FROM document_item_ingredients dii
             LEFT JOIN document_items di ON di.id = dii.document_item_id
             LEFT JOIN documents d ON d.id = di.document_id
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE dii.id = :id",
            ['id' => $ingredient_id]
        );
        $ing = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$ing) {
            echo json_encode(['sccss' => false, 'msg' => 'Ингредиент не найден']);
            exit;
        }
        if (!fncDocAccess($ing, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
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
            ['nomenclature_id' => $ing['nomenclature_id'], 'warehouse_id' => $ing['warehouse_in_id']]
        );
        $batches_raw = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        // Сетка резервов ДРУГИХ строк/ингредиентов этого документа — свой
        // собственный текущий резерв исключаем (redistribute должен видеть
        // ровно тот остаток, который реально доступен, включая уже занятое
        // им же самим — иначе форма покажет заниженный остаток при повторном
        // открытии).
        $reservations = fncBatchReservationsInDocument($ing['document_id'], null, $ingredient_id);

        $stmt = fncQuery(
            "SELECT batch_ledger_id, quantity FROM document_item_ingredient_batches WHERE document_item_ingredient_id = :id",
            ['id' => $ingredient_id]
        );
        $own_rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $own_map = [];
        foreach ($own_rows as $r) {
            $own_map[(int)$r['batch_ledger_id']] = (float)$r['quantity'];
        }

        $result = [];
        foreach ($batches_raw as $batch) {
            $batch_id = (int)$batch['id'];
            $own_qty = $own_map[$batch_id] ?? 0;
            // $reservations уже без собственного резерва ингредиента (см.
            // exclude_ingredient_id выше) — прибавлять $own_qty ещё раз нельзя,
            // иначе остаток завышается на размер собственного резерва.
            $net_remaining = round((float)$batch['quantity_remaining'] - ($reservations[$batch_id] ?? 0), 3);
            if ($net_remaining <= 0 && $own_qty <= 0) continue;

            $batch['quantity_remaining'] = $net_remaining;
            $batch['already_allocated'] = $own_qty;
            $result[] = $batch;
        }
        break;
    //==========================================================================
    // Шапка карточки Выпуска ПФ + список производимых номенклатур (is_produced=1)
    // для выбора. Отдельный action от document_header_data — там номенклатура
    // подбирается по остатку на складе списания (для Списания/Перемещения) или
    // по последней цене (для incoming), что не подходит для выбора ПФ.
    case 'production_header_data':
        $id = (int)($_POST['id'] ?? 0);

        $stmt = fncQuery(
            "SELECT d.id, d.number, d.document_date, d.status, d.organization_id, d.counterparty_id,
                    d.warehouse_in_id, d.warehouse_out_id,
                    TIMESTAMPDIFF(MINUTE, d.posting_started_at, NOW()) AS posting_minutes,
                    dt.code, dt.name AS type_name,
                    o.name AS organization_name, oot.abbreviation AS organization_abbreviation, oot.is_individual AS organization_is_individual,
                    w.name AS warehouse_name
             FROM documents d
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             LEFT JOIN organizations o ON o.id = d.organization_id
             LEFT JOIN organization_types oot ON oot.id = o.organization_type_id
             LEFT JOIN objects w ON w.id = d.warehouse_in_id
             WHERE d.id = :id",
            ['id' => $id]
        );
        $info = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
        if (empty($info)) {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не найден']);
            exit;
        }
        if (!fncDocAccess($info, false)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        $info['can_edit'] = fncDocAccess($info, true);
        $info = array_merge($info, fncPostingResetState($info['status'], $info['posting_minutes'], $info['can_edit']));
        $info = array_merge($info, fncUnpostCardState($info));
        $info['organization_display_name'] = fncOrgDisplayName($info['organization_name'], $info['organization_abbreviation'], $info['organization_is_individual']);

        $stmt = fncQuery("SELECT COUNT(*) AS cnt FROM document_items WHERE document_id = :id", ['id' => $id]);
        $is_empty = $stmt ? (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'] === 0 : false;

        $nomenclature = [];
        if ($info['status'] === 'draft') {
            $stmt = fncQuery(
                "SELECT id, name FROM nomenclature WHERE is_produced = 1 AND is_active = 1 ORDER BY name"
            );
            $nomenclature = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }

        $result = [
            'info'         => $info,
            'is_empty'     => $is_empty,
            'nomenclature' => $nomenclature,
        ];
        break;

    case 'save_ingredient_batches':
        $ingredient_id = (int)fncValFind('ingredient_id', $params);
        $batches_raw = fncValFind('batches', $params);

        if (!$ingredient_id || !$batches_raw) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указаны параметры']);
            exit;
        }

        $batches = json_decode($batches_raw, true);
        if (!is_array($batches) || empty($batches)) {
            echo json_encode(['sccss' => false, 'msg' => 'Некорректное распределение по партиям']);
            exit;
        }
        $batches = fncNormalizeBatchQuantities($batches);

        $stmt = fncQuery(
            "SELECT dii.id, dii.quantity, dii.batch_mode, d.id AS document_id, d.status, d.organization_id, d.warehouse_in_id, dt.code
             FROM document_item_ingredients dii
             LEFT JOIN document_items di ON di.id = dii.document_item_id
             LEFT JOIN documents d ON d.id = di.document_id
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE dii.id = :id",
            ['id' => $ingredient_id]
        );
        $ing = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!$ing) {
            echo json_encode(['sccss' => false, 'msg' => 'Ингредиент не найден']);
            exit;
        }
        if ($ing['status'] !== 'draft') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ уже проведён']);
            exit;
        }
        if ($ing['batch_mode'] !== 'manual') {
            echo json_encode(['sccss' => false, 'msg' => 'Ингредиент не в режиме ручного распределения']);
            exit;
        }
        if (!fncDocAccess($ing, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        global $pdo;

        try {
            $pdo->beginTransaction();

            // Сначала освобождаем прежнее распределение этого ингредиента —
            // партии зарезервированы только виртуально (см.
            // fncBatchReservationsInDocument), поэтому после освобождения
            // валидация новой раскладки ниже уже не учитывает собственный
            // старый резерв, и его не нужно отдельно исключать.
            if (!fncReleaseIngredientBatches($ingredient_id)) {
                throw new Exception('Не удалось освободить прежнее распределение');
            }

            $batches_error = fncValidateManualBatches($batches, $ing['warehouse_in_id'], $ing['document_id'], $ing['quantity']);
            if ($batches_error !== null) {
                $pdo->rollBack();
                echo json_encode(['sccss' => false, 'msg' => $batches_error]);
                exit;
            }

            foreach ($batches as $b) {
                $stmt = fncQuery(
                    "INSERT INTO document_item_ingredient_batches (document_item_ingredient_id, batch_ledger_id, quantity)
                     VALUES (:ingredient_id, :batch_ledger_id, :quantity)",
                    ['ingredient_id' => $ingredient_id, 'batch_ledger_id' => (int)$b['batch_id'], 'quantity' => $b['quantity']]
                );
                if (!$stmt) {
                    throw new Exception('Не удалось сохранить распределение');
                }
            }

            $pdo->commit();
            $result = ['sccss' => true];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            fncLog('Ошибка сохранения распределения ингредиента: ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Не удалось сохранить распределение']);
            exit;
        }
        break;

    //==========================================================================
    // Удаление строки ПФ целиком — вместе с ингредиентами (каскад по FK) и
    // освобождением их резервов по партиям (fncReleaseIngredientBatches —
    // только удаляет document_item_ingredient_batches, quantity_remaining
    // партий не трогает: реальное списание происходит только при проведении).
    case 'del_production_item':
        $item_id = (int)(fncValFind('id', $params) ?? ($_POST['id'] ?? 0));
        if (!$item_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указана строка']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT di.id, d.status, d.organization_id, dt.code
             FROM document_items di
             LEFT JOIN documents d ON d.id = di.document_id
             LEFT JOIN document_types dt ON dt.id = d.document_type_id
             WHERE di.id = :id",
            ['id' => $item_id]
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
        if (!fncDocAccess($item, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        global $pdo;

        try {
            $pdo->beginTransaction();

            $stmt = fncQuery(
                "SELECT id FROM document_item_ingredients WHERE document_item_id = :id",
                ['id' => $item_id]
            );
            if (!$stmt) {
                throw new Exception('Не удалось прочитать ингредиенты строки');
            }
            $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ingredients as $ing) {
                if (!fncReleaseIngredientBatches($ing['id'])) {
                    throw new Exception('Не удалось освободить партии ингредиента ' . $ing['id']);
                }
            }

            $stmt = fncQuery("DELETE FROM document_items WHERE id = :id", ['id' => $item_id]);
            if (!$stmt) {
                throw new Exception('Не удалось удалить строку');
            }

            $pdo->commit();
            $result = ['sccss' => true];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            fncLog('Ошибка удаления строки ПФ: ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Не удалось удалить строку']);
            exit;
        }
        break;

    //==========================================================================
    // Складские операции строки Выпуска ПФ — отдельный вид от обычного
    // document_item_ledger_data: здесь в рамках ОДНОЙ строки документа
    // участвуют РАЗНЫЕ номенклатуры (расход каждого ингредиента + приход
    // самого ПФ), поэтому каждая запись обязательно подписывается своим
    // названием номенклатуры, а группировка идёт по направлению
    // (расход/приход), а не по складу — он у Выпуска ПФ всегда один.
    case 'production_item_ledger_data':
        $item_id = (int)($_POST['item_id'] ?? 0);

        $stmt = fncQuery(
            "SELECT di.id, di.quantity, n.name AS nomenclature_name, d.status, d.organization_id, dt.code
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
        if (!fncDocAccess($item_check, false)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }
        if ($item_check['status'] !== 'posted') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ ещё не проведён']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT sl.id, sl.nomenclature_id, n.name AS nomenclature_name,
                    sl.quantity, sl.price, sl.sum, sl.total_quantity
             FROM stock_ledger sl
             LEFT JOIN nomenclature n ON n.id = sl.nomenclature_id
             WHERE sl.document_item_id = :item_id
             ORDER BY sl.quantity ASC, sl.id ASC",
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

    //==========================================================================
    // Отмена проведения (распроведение) уже проведённого документа — обратная
    // операция к post_document. Разрешена только если документ прямо сейчас
    // "последний" во всех цепочках, которые он затронул:
    //   A. все его строки stock_ledger (по парам nomenclature_id+warehouse_id)
    //      сейчас is_last = 1 — позже никто не трогал эту же номенклатуру на
    //      этом же складе;
    //   B. если есть counterparty_id — его строка settlements_ledger (по паре
    //      organization_id+counterparty_id) сейчас is_last = 1;
    //   C. нет ни одного документа в статусе draft/posting, ссылающегося на
    //      любой из складов этого документа — черновик может уже держать
    //      резерв конкретной партии (document_item_batches/
    //      document_item_ingredient_batches), и удаление партии его осиротит;
    //      поштучная проверка резерваций дороже и ненадёжнее, чем просто не
    //      разрешать отмену, пока на складе есть открытые черновики.
    // document_items/document_item_batches НЕ трогаем — документ возвращается
    // в draft с уже заполненными строками для ручной правки перед повторным
    // проведением. sum/tax_sum сбрасываются в 0 только для не-incoming
    // направлений (там эти поля считаются ТОЛЬКО при проведении); для
    // incoming — ручной ввод пользователя, не трогаем.
    case 'unpost_document':
        $document_id = (int)fncValFind('id', $params);
        if (!$document_id) {
            echo json_encode(['sccss' => false, 'msg' => 'Не указан документ']);
            exit;
        }

        $stmt = fncQuery(
            "SELECT d.id, d.status, d.organization_id, d.counterparty_id,
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
        if (!fncDocAccess($doc, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Нет доступа']);
            exit;
        }

        if (!in_array($doc['code'], POSTABLE_CODES, true)) {
            echo json_encode(['sccss' => false, 'msg' => 'Отмена проведения этого типа документа не реализована']);
            exit;
        }
        if ($doc['status'] !== 'posted') {
            echo json_encode(['sccss' => false, 'msg' => 'Документ не проведён']);
            exit;
        }

        $is_incoming = $doc['direction'] === 'incoming';
        $warehouse_ids = array_unique(array_filter([(int)$doc['warehouse_out_id'], (int)$doc['warehouse_in_id']]));

        global $pdo;

        try {
            $pdo->beginTransaction();

            // Блокируем склады документа — та же дисциплина, что в post_document,
            // чтобы ни проведение другого документа, ни смена склада на пустом
            // черновике не вклинились между проверкой C и самим откатом.
            $lock_ids = $warehouse_ids;
            sort($lock_ids);
            foreach ($lock_ids as $wid) {
                $stmt = fncQuery("SELECT id FROM objects WHERE id = :id FOR UPDATE", ['id' => $wid]);
                if (!$stmt) {
                    throw new Exception('Не удалось заблокировать склад');
                }
            }

            // Условия A, B, C — в fncUnpostBlockReason (та же проверка питает
            // кнопку в карточке документа, чтобы она не показывалась зря).
            $ledger_rows    = [];
            $settlement_row = null;
            $block_reason   = fncUnpostBlockReason($doc, true, $ledger_rows, $settlement_row);
            if ($block_reason !== null) {
                $pdo->rollBack();
                echo json_encode(['sccss' => false, 'msg' => $block_reason]);
                exit;
            }

            // Все условия пройдены — откатываем. Строки stock_ledger — в порядке
            // убывания id (см. fncUnwindStockLedgerRow).
            foreach ($ledger_rows as $row) {
                if (!fncUnwindStockLedgerRow($row)) {
                    throw new Exception('Не удалось откатить запись остатка ' . $row['id']);
                }
            }

            if ($settlement_row) {
                $stmt = fncQuery("DELETE FROM settlements_ledger WHERE id = :id", ['id' => $settlement_row['id']]);
                if (!$stmt) {
                    throw new Exception('Не удалось откатить взаиморасчёты');
                }

                $stmt = fncQuery(
                    "SELECT id FROM settlements_ledger
                     WHERE organization_id = :organization_id AND counterparty_id = :counterparty_id AND id < :id
                     ORDER BY id DESC LIMIT 1",
                    ['organization_id' => $doc['organization_id'], 'counterparty_id' => $doc['counterparty_id'], 'id' => $settlement_row['id']]
                );
                $prev_settlement = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
                if ($prev_settlement) {
                    $stmt = fncQuery("UPDATE settlements_ledger SET is_last = 1 WHERE id = :id", ['id' => $prev_settlement['id']]);
                    if (!$stmt) {
                        throw new Exception('Не удалось восстановить предыдущий баланс взаиморасчётов');
                    }
                }
            }

            // Строки Списания и Выпуска ПФ получают price/sum только при
            // проведении. После отмены обнуляем их, иначе в черновике остались бы
            // прошлые суммы, а "Итого" смешивало бы их с новыми строками.
            // Возвраты поставщику не трогаем: их цена известна с создания строки.
            if (in_array($doc['code'], ['writeoff', 'sf_production'], true)) {
                $stmt = fncQuery(
                    "UPDATE document_items SET price = NULL, sum = NULL WHERE document_id = :id",
                    ['id' => $document_id]
                );
                if (!$stmt) {
                    throw new Exception('Не удалось сбросить цены строк');
                }
            }

            $update_fields = "status = 'draft', posting_date = NULL, posting_started_at = NULL,
                               posting_finished_at = NULL, posted_by = NULL";
            if (!$is_incoming) {
                $update_fields .= ", sum = 0, tax_sum = 0";
            }

            $stmt = fncQuery(
                "UPDATE documents SET {$update_fields} WHERE id = :id AND status = 'posted'",
                ['id' => $document_id]
            );
            if (!$stmt || $stmt->rowCount() === 0) {
                throw new Exception('Документ уже изменился параллельно');
            }

            $pdo->commit();
            fncLog('Отменено проведение документа ' . $document_id . ' пользователем ' . $user_id);
            $result = ['sccss' => true];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            fncLog('Ошибка отмены проведения документа ' . $document_id . ': ' . $e->getMessage());
            echo json_encode(['sccss' => false, 'msg' => 'Не удалось отменить проведение документа']);
            exit;
        }
        break;

    default:
        echo json_encode(['sccss' => false, 'msg' => 'Неизвестное действие']);
        exit;
}

echo json_encode($result);
