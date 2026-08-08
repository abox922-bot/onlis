<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);

$unit_short_name = '';
$nom_info = send_request(array_merge($ses_info, ['action' => 'nomenclature_info', 'id' => $nomenclature_id]), 'noms');
if (is_array($nom_info) && !empty($nom_info)) {
    $unit_short_name = $nom_info['unit_short_name'] ?? '';
}
?>
<input type="hidden" id="inpPackageNomenclatureId" value="<?php echo (int)$nomenclature_id; ?>">
<form id="formPackageNew">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="inpPackageName" class="my-input-label">Название упаковки</label>
            <input type="text"
                class="form-in form-inp"
                id="inpPackageName"
                data-name="name"
                data-type="text"
                data-required="1"
                placeholder="Например: Коробка"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="inpPackageQuantity" class="my-input-label">
                Количество<?php echo $unit_short_name ? ' (' . htmlspecialchars($unit_short_name) . ')' : ''; ?>
            </label>
            <input type="text"
                class="form-in form-inp"
                id="inpPackageQuantity"
                data-name="quantity"
                data-type="digits_double"
                data-required="1"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input type="checkbox"
                    class="form-check-input form-inp"
                    role="switch"
                    id="chkPackageDefault"
                    data-name="is_default"
                    data-type="check">
                <label class="form-check-label" for="chkPackageDefault">Упаковка по умолчанию</label>
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
