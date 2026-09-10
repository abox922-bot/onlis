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
$is_empty    = $data['is_empty'];
$is_incoming = $info['direction'] === 'incoming';
?>
<form id="formEditHeader">
    <div class="row">
        <div class="col-12 mb-3">
            <label class="my-input-label">Организация</label>
            <?php if ($is_empty): ?>
                <select class="form-in form-inp" id="slctDocOrganization" data-name="organization_id" data-type="select" data-required="1">
                    <?php foreach ($data['organizations'] as $org): ?>
                        <option value="<?php echo (int)$org['id']; ?>" <?php echo $org['id'] == $info['organization_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($org['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <div class="form-context"><?php echo htmlspecialchars($info['organization_display_name'] ?? ''); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($is_incoming): ?>
        <div class="col-12 mb-3">
            <label class="my-input-label">Контрагент</label>
            <?php if ($is_empty): ?>
                <select id="slctDocCounterparty">
                    <?php foreach ($data['counterparties'] as $cp): ?>
                        <option value="<?php echo (int)$cp['id']; ?>" <?php echo $cp['id'] == $info['counterparty_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cp['display_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <div class="form-context"><?php echo htmlspecialchars($info['counterparty_display_name'] ?? ''); ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="col-12 mb-3">
            <label class="my-input-label">Склад поступления</label>
            <?php if ($is_empty): ?>
                <select class="form-in form-inp" id="slctDocWarehouseIn" data-name="warehouse_in_id" data-type="select" data-required="1">
                    <?php foreach ($data['warehouses'] as $w): ?>
                        <option value="<?php echo (int)$w['id']; ?>" data-organization="<?php echo (int)$w['organization_id']; ?>" <?php echo $w['id'] == $info['warehouse_in_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($w['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <div class="form-context"><?php echo htmlspecialchars($info['warehouse_in_name'] ?? ''); ?></div>
            <?php endif; ?>
        </div>
        <?php if ($is_incoming): ?>
        <div class="col-6 mb-3">
            <label for="inpDocNumber" class="my-input-label">Номер документа</label>
            <input type="text" class="form-in form-inp" id="inpDocNumber" data-name="number" data-type="text" data-required="1" value="<?php echo htmlspecialchars($info['number']); ?>">
        </div>
        <div class="col-6 mb-3">
            <label for="inpDocDate" class="my-input-label">Дата документа</label>
            <input type="date" class="form-in form-inp" id="inpDocDate" data-name="document_date" data-type="text" data-required="1" value="<?php echo htmlspecialchars($info['document_date']); ?>">
        </div>
        <?php endif; ?>
        <?php if ($is_incoming): ?>
        <div class="col-6 mb-3">
            <label for="inpDocSum" class="my-input-label">Сумма</label>
            <?php if ($is_empty): ?>
                <input type="text" class="form-in form-inp" id="inpDocSum" data-name="sum" data-type="digits_double" data-required="1" value="<?php echo htmlspecialchars($info['sum']); ?>">
            <?php else: ?>
                <div class="form-context"><?php echo fncFormatNumber($info['sum']); ?> ₽</div>
            <?php endif; ?>
        </div>
        <div class="col-6 mb-3">
            <label for="inpDocTaxSum" class="my-input-label">Сумма налога</label>
            <?php if ($is_empty): ?>
                <input type="text" class="form-in form-inp" id="inpDocTaxSum" data-name="tax_sum" data-type="digits_double" data-required="1" value="<?php echo htmlspecialchars($info['tax_sum']); ?>">
            <?php else: ?>
                <div class="form-context"><?php echo fncFormatNumber($info['tax_sum']); ?> ₽</div>
            <?php endif; ?>
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
            <button type="button" class="btn-danger-action" id="btnDeleteDocument">Удалить документ</button>
        </div>
    </div>
</form>
