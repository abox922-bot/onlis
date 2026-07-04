<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$st_id = (int)($_POST['st_id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_staff_info_access',
    'st_id'  => $st_id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}

$has_login = !empty($result['login']);
?>

<form id="formStaffAccess">
    <div class="row">

        <div class="col-12 mt-2">
            <div class="form-group-label">Доступ в систему</div>
        </div>

        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckIsActive" data-name="is-active" data-type="check"
                    <?php echo !empty($result['is_active']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckIsActive" id="lblIsActive">
                    <?php echo !empty($result['is_active']) ? 'Активен' : 'Заблокирован'; ?>
                </label>
            </div>
        </div>

        <div class="col-12 mt-2">
            <div class="form-group-label">Учётные данные</div>
        </div>

        <div class="col-12 mb-3">
            <label for="inpLogin" class="my-input-label">Логин</label>
            <input type="text"
                class="form-in form-inp"
                id="inpLogin"
                data-name="login"
                data-type="eng_text"
                value="<?php echo htmlspecialchars($result['login'] ?? ''); ?>"
                autocomplete="off"
                placeholder="латиница и цифры">
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>

        <div class="col-12 mb-3">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>

            <?php if ($has_login): ?>
            <button type="button" class="btn-danger-action ms-2" id="btnResetPass">
                Сбросить пароль
            </button>
            <?php endif; ?>
        </div>

        <div class="col-12 d-none" id="divNewPassword">
            <div class="form-group-label mb-2">Новый пароль</div>
            <div class="form-context" id="spnNewPassword" style="font-size: 1.4rem; letter-spacing: 0.1em;"></div>
            <div class="text-muted mt-1" style="font-size: 0.8rem;">
                Сохраните пароль — он больше не будет показан
            </div>
        </div>

    </div>
</form>

<script src="./_books_orgs/js/organization_info_staff_access.js?2026070401"></script>
