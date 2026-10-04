<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$item_id = (int)($_POST['item_id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'production_item_ledger_data', 'item_id' => $item_id]), 'docs');
if (!is_array($data) || isset($data['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Не удалось загрузить данные</div></div>';
    exit;
}

$ledger = $data['ledger'];
?>
<div class="mb-3">
    <div class="fw-bold" style="font-size: 1.1rem;"><?php echo htmlspecialchars($data['nomenclature_name']); ?></div>
    <div class="text-muted">Получено: <?php echo fncFormatNumber($data['quantity']); ?></div>
</div>
<hr>

<?php if (empty($ledger)): ?>
    <div class="empty-hint">
        <div class="empty-hint__text">Складские операции не найдены</div>
    </div>
<?php else: ?>
    <?php
    $expense_rows = array_filter($ledger, fn($r) => (float)$r['quantity'] < 0);
    $income_rows  = array_filter($ledger, fn($r) => (float)$r['quantity'] >= 0);
    ?>

    <?php if (!empty($expense_rows)): ?>
        <div class="form-context mb-2">
            <i class="bi bi-box-arrow-up" style="margin-right: 6px;"></i>Расход ингредиентов
        </div>
        <?php $index = 0; ?>
        <?php foreach ($expense_rows as $row): ?>
            <?php $has_details = !empty($row['consumption']); ?>
            <div class="composition-row<?php echo $has_details ? ' composition-row--clickable' : ''; ?>" data-ledger-toggle="<?php echo $index; ?>">
                <div class="composition-row__main">
                    <div class="composition-row__name"><?php echo htmlspecialchars($row['nomenclature_name']); ?></div>
                    <div class="composition-row__calc text-muted">
                        <?php echo fncFormatNumber(abs($row['quantity'])); ?> по <?php echo fncFormatNumber($row['price']); ?> ₽
                    </div>
                    <div class="text-muted" style="font-size: 0.78rem;">Остаток на складе: <?php echo fncFormatNumber($row['total_quantity']); ?></div>
                </div>
                <div class="text-end me-2 d-flex align-items-center gap-2">
                    <div class="composition-row__sum"><?php echo fncFormatNumber(abs($row['sum'])); ?> ₽</div>
                    <?php if ($has_details): ?>
                        <i class="bi bi-chevron-down text-muted" id="chevronLedgerExpense<?php echo $index; ?>" style="font-size: 0.8rem;"></i>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($has_details): ?>
                <div class="ps-3 mb-2 d-none" id="divLedgerDetailsExpense<?php echo $index; ?>">
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
                                <div class="composition-row__sum"><?php echo fncFormatNumber($c['quantity']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php $index++; ?>
        <?php endforeach; ?>
        <hr>
    <?php endif; ?>

    <?php if (!empty($income_rows)): ?>
        <div class="form-context mb-2">
            <i class="bi bi-box-arrow-in-down" style="margin-right: 6px;"></i>Приход ПФ
        </div>
        <?php foreach ($income_rows as $row): ?>
            <div class="composition-row">
                <div class="composition-row__main">
                    <div class="composition-row__name"><?php echo htmlspecialchars($row['nomenclature_name']); ?></div>
                    <div class="composition-row__calc text-muted">
                        <?php echo fncFormatNumber($row['quantity']); ?> по <?php echo fncFormatNumber($row['price']); ?> ₽
                    </div>
                    <div class="text-muted" style="font-size: 0.78rem;">Остаток на складе: <?php echo fncFormatNumber($row['total_quantity']); ?></div>
                </div>
                <div class="text-end me-2">
                    <div class="composition-row__sum"><?php echo fncFormatNumber($row['sum']); ?> ₽</div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
