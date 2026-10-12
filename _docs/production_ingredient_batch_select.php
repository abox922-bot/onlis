<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
$needed_quantity = $_POST['needed_quantity'] ?? '0';

$batches = send_request(array_merge($ses_info, [
    'action' => 'ingredient_batches_data',
    'ingredient_id' => $ingredient_id,
]), 'docs');

if (!is_array($batches) || isset($batches['sccss'])) {
    $batches = [];
}
?>
<input type="hidden" id="inpIngredientBatchId" value="<?php echo (int)$ingredient_id; ?>">
<input type="hidden" id="inpIngredientBatchNeededQuantity" value="<?php echo htmlspecialchars($needed_quantity); ?>">

<div class="mb-3">
    <span class="text-muted">Нужно распределить:</span>
    <strong><?php echo fncFormatQuantity((float)$needed_quantity); ?></strong>
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
                    от <?php echo date('d.m.Y', strtotime($batch['date'])); ?> · <?php echo fncFormatNumber($batch['price']); ?> ₽/ед. · остаток <?php echo fncFormatQuantity($batch['quantity_remaining']); ?>
                </div>
            </div>
            <div style="width: 100px;">
                <input type="text" class="form-in batch-quantity-input" autocomplete="off" placeholder="0"
                    <?php if ((float)$batch['already_allocated'] > 0): ?>value="<?php echo htmlspecialchars($batch['already_allocated']); ?>"<?php endif; ?>>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="composition-total mt-3">
    <span>Распределено</span>
    <span id="spnIngredientBatchDistributed">0</span>
</div>
<div class="composition-total mt-1">
    <span>Осталось распределить</span>
    <span id="spnIngredientBatchRemaining"><?php echo fncFormatQuantity((float)$needed_quantity); ?></span>
</div>

<div class="col-12 mt-2 d-none" id="divIngredientBatchFormError">
    <div class="form-error-msg" id="spnIngredientBatchFormError"></div>
</div>
<div class="mt-3">
    <button type="button" class="btn-action-main" id="btnConfirmIngredientBatches">Подтвердить распределение</button>
</div>
