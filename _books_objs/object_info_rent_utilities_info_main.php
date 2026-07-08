<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$utility = send_request(array_merge($ses_info, ['action' => 'object_utility_type_info', 'id' => $id]), 'objs');
if (!is_array($utility) || isset($utility['sccss'])) {
    $utility = [];
}
?>
<form id="formUtilityMain">
    <div class="row">
        <div class="col-12 col-md-6 mb-3">
            <label class="my-input-label" for="inpUtilityName2">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpUtilityName2"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off"
                value="<?php echo htmlspecialchars($utility['name'] ?? ''); ?>">
        </div>
        <div class="col-12 col-md-6 mb-3">
            <label class="my-input-label" for="inpTariff">Текущий тариф</label>
            <input type="text"
                class="form-in form-inp"
                id="inpTariff"
                data-name="current_tariff"
                data-type="digits_double"
                data-required="1"
                autocomplete="off"
                value="<?php echo htmlspecialchars($utility['current_tariff'] ?? ''); ?>">
        </div>
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckUtilityActual" data-name="is_active" data-type="check"
                    <?php echo !empty($utility['is_active']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckUtilityActual">Активный</label>
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
<script src="./_books_objs/js/object_info_rent_utilities_info_main.js?2026070800"></script>
