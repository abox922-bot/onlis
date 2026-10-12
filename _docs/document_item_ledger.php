<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$item_id = (int)($_POST['item_id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'document_item_ledger_data', 'item_id' => $item_id]), 'docs');
if (!is_array($data) || isset($data['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Не удалось загрузить данные</div></div>';
    exit;
}

$ledger = $data['ledger'];

$groups = [];
foreach ($ledger as $row) {
    $wid = $row['warehouse_id'];
    if (!isset($groups[$wid])) {
        $groups[$wid] = [
            'name'        => $row['warehouse_name'],
            'is_incoming' => (float)$row['quantity'] >= 0,
            'rows'        => [],
        ];
    }
    $groups[$wid]['rows'][] = $row;
}
?>
<div class="mb-3">
    <div class="fw-bold" style="font-size: 1.1rem;"><?php echo htmlspecialchars($data['nomenclature_name']); ?></div>
    <div class="text-muted">Количество по строке: <?php echo fncFormatQuantity($data['quantity']); ?></div>
</div>
<hr>

<?php if (empty($groups)): ?>
    <div class="empty-hint">
        <div class="empty-hint__text">Складские операции не найдены</div>
    </div>
<?php else: ?>
    <?php $global_index = 0; ?>
    <?php foreach ($groups as $group): ?>
        <div class="form-context mb-2">
            <i class="bi <?php echo $group['is_incoming'] ? 'bi-box-arrow-in-down' : 'bi-box-arrow-up'; ?>" style="margin-right: 6px;"></i>
            <?php echo htmlspecialchars($group['name']); ?>. <?php echo $group['is_incoming'] ? 'Приход' : 'Расход'; ?>
        </div>

        <?php foreach ($group['rows'] as $row): ?>
            <?php $current_index = $global_index++; ?>
            <?php $has_details = !empty($row['consumption']); ?>
            <div class="composition-row<?php echo $has_details ? ' composition-row--clickable' : ''; ?>" data-ledger-toggle="<?php echo $current_index; ?>">
                <div class="composition-row__main">
                    <?php if ($row['source_warehouse_name']): ?>
                        <div class="composition-row__name">
                            <span class="text-muted" style="font-weight: 400;">со склада «<?php echo htmlspecialchars($row['source_warehouse_name']); ?>»</span>
                        </div>
                    <?php endif; ?>
                    <div class="composition-row__calc text-muted">
                        <?php echo fncFormatQuantity(abs($row['quantity'])); ?> по <?php echo fncFormatNumber($row['price']); ?> ₽
                    </div>
                    <div class="text-muted" style="font-size: 0.78rem;">Остаток на складе: <?php echo fncFormatQuantity($row['total_quantity']); ?></div>
                </div>
                <div class="text-end me-2 d-flex align-items-center gap-2">
                    <div class="composition-row__sum"><?php echo fncFormatNumber(abs($row['sum'])); ?> ₽</div>
                    <?php if ($has_details): ?>
                        <i class="bi bi-chevron-down text-muted" id="chevronLedger<?php echo $current_index; ?>" style="font-size: 0.8rem;"></i>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($has_details): ?>
                <div class="ps-3 mb-2 d-none" id="divLedgerDetails<?php echo $current_index; ?>">
                    <div class="form-group-label mt-2 mb-2">Списано из партий</div>
                    <?php foreach ($row['consumption'] as $c): ?>
                        <div class="composition-row">
                            <div class="composition-row__main">
                                <div class="composition-row__name"><?php echo htmlspecialchars($c['batch_document_type_name']); ?> №<?php echo htmlspecialchars($c['batch_document_number']); ?></div>
                                <div class="composition-row__calc text-muted">
                                    от <?php echo date('d.m.Y', strtotime($c['batch_date'])); ?> · <?php echo fncFormatNumber($c['price']); ?> ₽/ед.
                                </div>
                            </div>
                            <div class="text-end me-2">
                                <div class="composition-row__sum"><?php echo fncFormatQuantity($c['quantity']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <hr>
    <?php endforeach; ?>
<?php endif; ?>
