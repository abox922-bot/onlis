<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_department_info',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}

$has_staff = ($result['staff_count'] ?? 0) > 0;
?>

<form id="formInfo">
    <div class="row">

        <div class="col-12 mb-3">
            <label for="inpDepName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpDepName"
                data-name="dep-name"
                data-type="text"
                data-required="1"
                value="<?php echo htmlspecialchars($result['name'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckDepActive"
                    data-name="dep-active"
                    data-type="check"
                    <?php echo !empty($result['is_active']) ? 'checked' : ''; ?>
                    <?php echo $has_staff ? 'disabled' : ''; ?>>
                <label class="form-check-label" for="chckDepActive" id="lblDepActive">
                    <?php echo !empty($result['is_active']) ? 'Активный' : 'Архивный'; ?>
                </label>
            </div>
            <?php if ($has_staff): ?>
                <div class="text-muted mt-1" style="font-size: 0.8rem;">
                    Нельзя архивировать — есть активные сотрудники
                </div>
            <?php endif; ?>
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
        </div>

    </div>
</form>
