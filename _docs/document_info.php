<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'document_info_data', 'id' => $id]), 'docs');
if (!is_array($data) || isset($data['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Документ не найден</div></div>';
    exit;
}

$info        = $data['info'];
$items       = $data['items'];
$is_draft    = $info['status'] === 'draft';
$is_incoming = $info['direction'] === 'incoming';
?>
<input type="hidden" id="inpDocumentId" value="<?php echo (int)$info['id']; ?>">

<div class="doc-header-compact doc-header-readonly mb-3<?php echo $is_draft ? ' doc-header-compact--clickable' : ''; ?>" id="divDocHeader">
    <?php if ($is_draft): ?>
        <i class="bi bi-pencil doc-header-compact__edit-icon"></i>
    <?php endif; ?>

    <div class="doc-header-compact__grid">
        <div class="doc-header-compact__field">
            <i class="bi bi-building doc-header-compact__icon"></i>
            <span class="doc-header-compact__label">Организация:</span>
            <span><?php echo htmlspecialchars($info['organization_display_name'] ?? ''); ?></span>
        </div>

        <?php if ($is_incoming): ?>
        <div class="doc-header-compact__field">
            <i class="bi bi-person-badge doc-header-compact__icon"></i>
            <span class="doc-header-compact__label">Контрагент:</span>
            <span><?php echo htmlspecialchars($info['counterparty_display_name'] ?? ''); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($info['warehouse_out_name']): ?>
        <div class="doc-header-compact__field">
            <i class="bi bi-box-arrow-up doc-header-compact__icon"></i>
            <span class="doc-header-compact__label">Склад списания:</span>
            <span><?php echo htmlspecialchars($info['warehouse_out_name']); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($info['warehouse_in_name']): ?>
        <div class="doc-header-compact__field">
            <i class="bi bi-box-arrow-in-down doc-header-compact__icon"></i>
            <span class="doc-header-compact__label">Склад поступления:</span>
            <span><?php echo htmlspecialchars($info['warehouse_in_name']); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($is_incoming): ?>
        <div class="doc-header-compact__field">
            <i class="bi bi-cash-coin doc-header-compact__icon"></i>
            <span class="doc-header-compact__label">Сумма:</span>
            <span><?php echo fncFormatNumber($info['sum']); ?> ₽ (НДС: <?php echo fncFormatNumber($info['tax_sum']); ?> ₽)</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($is_draft): ?>
<div class="row" id="divNewItemForm">
    <div class="col-12 col-md mb-2">
        <label class="my-input-label">Номенклатура</label>
        <select id="slctItemNomenclature">
            <option value="">Выберите номенклатуру</option>
            <?php foreach ($data['nomenclature'] as $n): ?>
                <option value="<?php echo (int)$n['id']; ?>"<?php if (isset($n['last_price']) && $n['last_price'] !== null): ?> data-price="<?php echo htmlspecialchars($n['last_price']); ?>"<?php endif; ?>><?php echo htmlspecialchars($n['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <label class="my-input-label">Количество</label>
        <input type="text" class="form-in" id="inpItemQuantity" autocomplete="off">
    </div>
    <?php if ($is_incoming): ?>
    <div class="col-6 col-md-2 mb-2">
        <label class="my-input-label">Цена</label>
        <input type="text" class="form-in" id="inpItemPrice" autocomplete="off">
    </div>
    <div class="col-6 col-md-2 mb-2">
        <label class="my-input-label">Сумма</label>
        <input type="text" class="form-in" id="inpItemSum" autocomplete="off">
    </div>
    <div class="col-6 col-md-2 mb-2">
        <label class="my-input-label">НДС</label>
        <input type="text" class="form-in" id="inpItemTaxSum" autocomplete="off"
            <?php if ((float)$info['tax_sum'] === 0.0): ?>disabled value="0"<?php endif; ?>>
    </div>
    <?php endif; ?>
    <div class="col-12 mt-2 d-none" id="divItemFormError">
        <div class="form-error-msg" id="spnItemFormError"></div>
    </div>
    <div class="col-12 mt-2">
        <button type="button" class="btn-action-main" id="btnAddItem">
            <span id="btnAddItemText">Добавить строку</span>
            <div class="spinner-border spinner-border-sm d-none" id="divAddItemLoading"></div>
        </button>
    </div>
</div>
<hr>
<?php endif; ?>

<div id="divDocumentItemsList"></div>
