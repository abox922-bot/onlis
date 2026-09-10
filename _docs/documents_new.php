<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$form_data = send_request(array_merge($ses_info, ['action' => 'document_new_form_data']), 'docs');
if (!is_array($form_data) || isset($form_data['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Не удалось загрузить данные формы</div></div>';
    exit;
}
?>
<form id="formNew">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="slctOrganization" class="my-input-label">Организация</label>
            <select class="form-in form-inp" id="slctOrganization" data-name="organization_id" data-type="select" data-required="1">
                <option value="0">Выберите организацию</option>
                <?php foreach ($form_data['organizations'] as $org): ?>
                    <option value="<?php echo (int)$org['id']; ?>"><?php echo htmlspecialchars($org['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="slctDocumentType" class="my-input-label">Тип документа</label>
            <select class="form-in form-inp" id="slctDocumentType" data-name="type_code" data-type="select" data-required="1">
                <option value="0">Выберите тип документа</option>
                <?php foreach ($form_data['document_types'] as $dt): ?>
                    <option value="<?php echo htmlspecialchars($dt['code']); ?>"
                        data-direction="<?php echo htmlspecialchars($dt['direction']); ?>"
                        data-warehouse-count="<?php echo (int)$dt['warehouse_count']; ?>"
                        data-warehouse-field="<?php echo htmlspecialchars($dt['warehouse_field'] ?? ''); ?>"
                        data-numbering-mode="<?php echo htmlspecialchars($dt['numbering_mode']); ?>">
                        <?php echo htmlspecialchars($dt['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <div id="divDynamicFields" class="row d-none">

                <div class="col-12 mb-3 d-none" id="divCounterpartyWrap">
                    <label for="slctCounterparty" class="my-input-label">Контрагент</label>
                    <select id="slctCounterparty">
                        <option value="">Выберите контрагента</option>
                        <?php foreach ($form_data['counterparties'] as $cp): ?>
                            <option value="<?php echo (int)$cp['id']; ?>"><?php echo htmlspecialchars($cp['display_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 mb-3 d-none" id="divWarehouseOutWrap">
                    <label for="slctWarehouseOut" class="my-input-label">Склад списания</label>
                    <select class="form-in" id="slctWarehouseOut" data-name="warehouse_out_id" data-type="select" data-required="1">
                        <option value="0">Выберите склад</option>
                        <?php foreach ($form_data['warehouses'] as $w): ?>
                            <option value="<?php echo (int)$w['id']; ?>" data-organization="<?php echo (int)$w['organization_id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 mb-3 d-none" id="divWarehouseInWrap">
                    <label for="slctWarehouseIn" class="my-input-label">Склад поступления</label>
                    <select class="form-in" id="slctWarehouseIn" data-name="warehouse_in_id" data-type="select" data-required="1">
                        <option value="0">Выберите склад</option>
                        <?php foreach ($form_data['warehouses'] as $w): ?>
                            <option value="<?php echo (int)$w['id']; ?>" data-organization="<?php echo (int)$w['organization_id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 mb-3 d-none" id="divDocumentNumberWrap">
                    <label for="inpDocumentNumber" class="my-input-label">Номер документа</label>
                    <input type="text" class="form-in" id="inpDocumentNumber" data-name="number" data-type="text" data-required="1" autocomplete="off">
                </div>

                <div class="col-6 mb-3 d-none" id="divDocumentDateWrap">
                    <label for="inpDocumentDate" class="my-input-label">Дата документа</label>
                    <input type="date" class="form-in" id="inpDocumentDate" data-name="document_date" data-type="text" data-required="1" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="col-6 mb-3 d-none" id="divSumWrap">
                    <label for="inpSum" class="my-input-label">Сумма</label>
                    <input type="text" class="form-in" id="inpSum" data-name="sum" data-type="digits_double" data-required="1" autocomplete="off">
                </div>

                <div class="col-6 mb-3 d-none" id="divTaxSumWrap">
                    <label for="inpTaxSum" class="my-input-label">Сумма налога</label>
                    <input type="text" class="form-in" id="inpTaxSum" data-name="tax_sum" data-type="digits_double" data-required="1" autocomplete="off">
                </div>

            </div>
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <div class="col-12 mt-3">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
        </div>
    </div>
</form>
