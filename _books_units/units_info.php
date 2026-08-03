<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$unit = send_request(array_merge($ses_info, ['action' => 'unit_info', 'id' => $id]), 'unt');
if (!is_array($unit) || isset($unit['sccss'])) {
    $unit = [];
}
?>
<form id="formInfo">
    <div class="row">
        <div class="col-12 col-md-6 mb-3">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off"
                value="<?php echo htmlspecialchars($unit['name'] ?? ''); ?>">
        </div>
        <div class="col-12 col-md-6 mb-3">
            <label for="inpShortName" class="my-input-label">Краткое обозначение</label>
            <input type="text"
                class="form-in form-inp"
                id="inpShortName"
                data-name="short_name"
                data-type="text"
                autocomplete="off"
                value="<?php echo htmlspecialchars($unit['short_name'] ?? ''); ?>">
        </div>
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckIsFloat" data-name="is_float" data-type="check"
                    <?php echo !empty($unit['is_float']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckIsFloat">Дробное значение</label>
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
