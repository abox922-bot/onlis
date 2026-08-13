<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$units = send_request(array_merge($ses_info, ['action' => 'units_list']), 'unt');
if (!is_array($units) || isset($units['sccss'])) {
    $units = [];
}
?>
<form id="formNew">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="slctUnit" class="my-input-label">Единица измерения</label>
            <select class="form-in form-inp" id="slctUnit" data-name="unit_id" data-type="select" data-required="1">
                <option value="0">Выберите единицу</option>
                <?php foreach ($units as $unit): ?>
                    <option value="<?php echo (int)$unit['id']; ?>"><?php echo htmlspecialchars($unit['name']); ?></option>
                <?php endforeach; ?>
            </select>
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
