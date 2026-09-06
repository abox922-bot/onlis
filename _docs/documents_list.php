<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

// ВРЕМЕННЫЕ ТЕСТОВЫЕ ДАННЫЕ для оценки вёрстки — заменить на send_request к
// app_docs.php (action documents_list), когда согласуем визуал.
$result = [
    ['id' => 1, 'code' => 'incoming_upd', 'type_name' => 'УПД', 'number' => '1316104', 'status' => 'posted',
        'counterparty_name' => 'АО "Ирбитский молочный завод"', 'warehouse_in_name' => 'ONL.IS Coffee Ленина 82А', 'warehouse_out_name' => null,
        'sum' => 45870.50, 'document_date' => '2026-09-05'],
    ['id' => 2, 'code' => 'incoming_waybill', 'type_name' => 'Товарная накладная', 'number' => '2066586', 'status' => 'posted',
        'counterparty_name' => 'АО "СМАК"', 'warehouse_in_name' => 'ONL.IS Coffee Ленина 82А', 'warehouse_out_name' => null,
        'sum' => 12300.00, 'document_date' => '2026-09-05'],
    ['id' => 3, 'code' => 'writeoff', 'type_name' => 'Списание', 'number' => '8842', 'status' => 'posted',
        'counterparty_name' => null, 'warehouse_in_name' => null, 'warehouse_out_name' => 'ONL.IS Coffee Ленина 82А',
        'sum' => 1540.00, 'document_date' => '2026-09-04'],
    ['id' => 4, 'code' => 'stock_adjustment', 'type_name' => 'Оприходование', 'number' => '10001', 'status' => 'draft',
        'counterparty_name' => null, 'warehouse_in_name' => 'ONL.IS Coffee Ленина 82А', 'warehouse_out_name' => null,
        'sum' => 3200.00, 'document_date' => '2026-09-04'],
    ['id' => 5, 'code' => 'realization', 'type_name' => 'Реализация', 'number' => '5316', 'status' => 'posted',
        'counterparty_name' => null, 'warehouse_in_name' => null, 'warehouse_out_name' => 'ONL.IS Coffee Ленина 82А',
        'sum' => 28450.00, 'document_date' => '2026-09-04'],
    ['id' => 6, 'code' => 'sf_production', 'type_name' => 'Выпуск полуфабрикатов', 'number' => '19594', 'status' => 'posting',
        'counterparty_name' => null, 'warehouse_in_name' => 'ONL.IS Coffee Ленина 82А', 'warehouse_out_name' => null,
        'sum' => 0.00, 'document_date' => '2026-09-04'],
    ['id' => 7, 'code' => 'transfer', 'type_name' => 'Перемещение', 'number' => '17953', 'status' => 'posted',
        'counterparty_name' => null, 'warehouse_in_name' => 'ONL.IS Coffee Ленина 82А', 'warehouse_out_name' => 'Основной склад',
        'sum' => 9800.00, 'document_date' => '2026-09-03'],
    ['id' => 8, 'code' => 'incoming_receipt', 'type_name' => 'Товарный чек', 'number' => 'МН-29537', 'status' => 'posted',
        'counterparty_name' => 'МАГАЗИН', 'warehouse_in_name' => 'ONL.IS Coffee Ленина 82А', 'warehouse_out_name' => null,
        'sum' => 670.00, 'document_date' => '2026-09-03'],
];

// Группировка по дате — с человекочитаемыми ярлыками для сегодня/вчера
$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$groups = [];
foreach ($result as $row) {
    $groups[$row['document_date']][] = $row;
}
krsort($groups);

function fncDateGroupLabel($date, $today, $yesterday) {
    if ($date === $today) return 'Сегодня';
    if ($date === $yesterday) return 'Вчера';
    $months = [1=>'января',2=>'февраля',3=>'марта',4=>'апреля',5=>'мая',6=>'июня',7=>'июля',8=>'августа',9=>'сентября',10=>'октября',11=>'ноября',12=>'декабря'];
    $ts = strtotime($date);
    return (int)date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
?>

<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-file-earmark-text empty-hint__icon"></i>
        <div class="empty-hint__text">Документы не найдены</div>
    </div>
<?php else: ?>
    <?php foreach ($groups as $date => $items): ?>
        <div class="doc-date-group"><?php echo fncDateGroupLabel($date, $today, $yesterday); ?></div>
        <?php foreach ($items as $value): ?>
            <div class="doc-card itemTr<?php echo $value['status'] === 'posted' ? ' is-posted' : ''; ?>" data-id="<?php echo (int)$value['id']; ?>">
                <div class="doc-card__main">
                    <div class="doc-card__title-row">
                        <div class="doc-card__title itemName" data-id="<?php echo (int)$value['id']; ?>">
                            <?php echo htmlspecialchars($value['type_name']); ?> №<?php echo htmlspecialchars($value['number']); ?>
                        </div>
                    </div>
                    <?php if ($value['counterparty_name']): ?>
                        <div class="doc-card__counterparty"><?php echo htmlspecialchars($value['counterparty_name']); ?></div>
                    <?php endif; ?>
                    <div class="doc-card__meta">
                        <?php if ($value['warehouse_in_name']): ?>
                            <span class="doc-card__meta-line">Склад поступления: <?php echo htmlspecialchars($value['warehouse_in_name']); ?></span>
                        <?php endif; ?>
                        <?php if ($value['warehouse_out_name']): ?>
                            <span class="doc-card__meta-line">Склад списания: <?php echo htmlspecialchars($value['warehouse_out_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="doc-card__sum"><?php echo number_format($value['sum'], 2, ',', ' '); ?> ₽</div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>
