<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$document_id = (int)($_POST['document_id'] ?? 0);
$nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);
$warehouse_id = (int)($_POST['warehouse_id'] ?? 0);
$needed_quantity = $_POST['needed_quantity'] ?? '0';

$batches = send_request(array_merge($ses_info, [
    'action' => 'document_item_batches_data',
    'document_id' => $document_id,
    'nomenclature_id' => $nomenclature_id,
    'warehouse_id' => $warehouse_id,
]), 'docs');

if (!is_array($batches) || isset($batches['sccss'])) {
    $batches = [];
}
?>
<input type="hidden" id="inpBatchNeededQuantity" value="<?php echo htmlspecialchars($needed_quantity); ?>">

<div class="mb-3">
    <span class="text-muted">Нужно распределить:</span>
    <strong><?php echo fncFormatNumber((float)$needed_quantity); ?></strong>
</div>

<?php if (empty($batches)): ?>
    <div class="empty-hint">
        <div class="empty-hint__text">Нет доступных партий</div>
    </div>
<?php else: ?>
    <?php foreach ($batches as $batch): ?>
        <div class="composition-row" data-batch-id="<?php echo (int)$batch['id']; ?>" data-batch-remaining="<?php echo $batch['quantity_remaining']; ?>">
            <div class="composition-row__main">
                <div class="composition-row__name"><?php echo htmlspecialchars($batch['document_type_name']); ?> №<?php echo htmlspecialchars($batch['document_number']); ?></div>
                <div class="composition-row__calc text-muted">
                    от <?php echo date('d.m.Y', strtotime($batch['date'])); ?> · <?php echo fncFormatNumber($batch['price']); ?> ₽/ед. · остаток <?php echo fncFormatNumber($batch['quantity_remaining']); ?>
                </div>
            </div>
            <div style="width: 100px;">
                <input type="text" class="form-in batch-quantity-input" autocomplete="off" placeholder="0">
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="composition-total mt-3">
    <span>Распределено</span>
    <span id="spnBatchDistributed">0</span>
</div>

<div class="col-12 mt-2 d-none" id="divBatchFormError">
    <div class="form-error-msg" id="spnBatchFormError"></div>
</div>
<div class="mt-3">
    <button type="button" class="btn-action-main" id="btnConfirmBatches">Подтвердить распределение</button>
</div>
