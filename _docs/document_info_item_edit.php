<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$item_id = (int)($_POST['item_id'] ?? 0);

$item = send_request(array_merge($ses_info, ['action' => 'document_item_info', 'id' => $item_id]), 'docs');
if (!is_array($item) || isset($item['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Строка не найдена</div></div>';
    exit;
}

$is_incoming = $item['is_incoming'];
$show_price  = $item['requires_price'];
$has_batches = $item['has_batches'];
?>
<?php if ($has_batches): ?>
<div class="mb-3">
    <div class="form-context"><?php echo htmlspecialchars($item['nomenclature_name']); ?></div>
</div>
<div class="mb-3">
    <span class="text-muted">Количество:</span>
    <strong><?php echo htmlspecialchars($item['quantity']); ?></strong>
</div>
<button type="button" class="btn-danger-action" id="btnDeleteItem"
    data-nomenclature-id="<?php echo (int)$item['nomenclature_id']; ?>"
    data-quantity="<?php echo htmlspecialchars($item['quantity']); ?>">Удалить строку</button>
<?php else: ?>
<form id="formEditItem">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-context"><?php echo htmlspecialchars($item['nomenclature_name']); ?></div>
        </div>

        <div class="col-12 col-md-3 mb-3">
            <label for="inpEditItemQuantity" class="my-input-label">Количество</label>
            <input type="text" class="form-in form-inp" id="inpEditItemQuantity" data-name="quantity" data-type="digits_double" data-required="1" value="<?php echo htmlspecialchars($item['quantity']); ?>">
        </div>

        <?php if ($show_price): ?>
        <div class="col-12 col-md-3 mb-3">
            <label for="inpEditItemPrice" class="my-input-label">Цена</label>
            <input type="text" class="form-in form-inp" id="inpEditItemPrice" data-name="price" data-type="digits_double" data-required="1" value="<?php echo preg_replace('/0{1,2}$/', '', number_format((float)$item['price'], 4, '.', '')); ?>">
        </div>
        <div class="col-12 col-md-3 mb-3">
            <label for="inpEditItemSum" class="my-input-label">Сумма</label>
            <input type="text" class="form-in form-inp" id="inpEditItemSum" data-name="sum" data-type="digits_double" data-required="1" value="<?php echo htmlspecialchars($item['sum']); ?>">
        </div>
        <?php endif; ?>
        <?php if ($is_incoming): ?>
        <div class="col-12 col-md-3 mb-3">
            <label for="inpEditItemTaxSum" class="my-input-label">НДС</label>
            <input type="text" class="form-in form-inp" id="inpEditItemTaxSum" data-name="tax_sum" data-type="digits_double" data-required="1" value="<?php echo htmlspecialchars($item['tax_sum']); ?>"
                <?php if ((float)$item['document_tax_sum'] === 0.0): ?>disabled<?php endif; ?>>
        </div>
        <?php endif; ?>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <div class="col-12 mt-3 d-flex gap-2">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
            <button type="button" class="btn-danger-action" id="btnDeleteItem">Удалить строку</button>
        </div>
    </div>
</form>
<?php endif; ?>
