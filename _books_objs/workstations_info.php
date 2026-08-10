<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$workstation = send_request(array_merge($ses_info, ['action' => 'workstation_info', 'id' => $id]), 'objs');
if (!is_array($workstation) || isset($workstation['sccss'])) {
    $workstation = [];
}
?>
<form id="formInfo">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off"
                value="<?php echo htmlspecialchars($workstation['name'] ?? ''); ?>">
        </div>
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckHasPosAccess" data-name="has_pos_access" data-type="check"
                    <?php echo !empty($workstation['has_pos_access']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckHasPosAccess">Доступ к кассовому интерфейсу</label>
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
