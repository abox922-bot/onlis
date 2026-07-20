<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$user_id = (int)($_POST['user_id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action'  => 'info_access',
    'user_id' => $user_id,
]), 'users');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}

$is_archived = empty($result['actual']);
?>
<form id="formUserAccess">
    <div class="row">

        <?php if ($is_archived): ?>
        <div class="col-12 mb-3">
            <div class="form-context" style="border-left-color:#dc3545;">
                Сотрудник в архиве. Доступ и учётные данные недоступны для редактирования, пока запись не восстановлена.
            </div>
        </div>
        <?php endif; ?>

        <div class="col-12 mt-2">
            <div class="form-group-label">Доступ в систему</div>
        </div>
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckIsActive" data-name="is-active" data-type="check"
                    <?php echo !empty($result['is_active']) ? 'checked' : ''; ?>
                    <?php echo $is_archived ? 'disabled' : ''; ?>>
                <label class="form-check-label" for="chckIsActive" id="lblIsActive">
                    <?php echo !empty($result['is_active']) ? 'Активен' : 'Заблокирован'; ?>
                </label>
            </div>
        </div>

        <div class="col-12 mt-2">
            <div class="form-group-label">Учётные данные</div>
        </div>
        <div class="col-12 col-md-6 mb-3">
            <label class="my-input-label">Логин для входа</label>
            <div class="input-group">
                <input type="text" class="form-in form-inp" id="inpLogin"
                    data-name="login" data-type="digits_only"
                    data-length="5"
                    placeholder="Сгенерируйте логин"
                    value="<?php echo htmlspecialchars($result['login'] ?? ''); ?>"
                    disabled>
                <button type="button" class="btn btn-generate" id="btnGenLogin" title="Сгенерировать логин"
                    <?php echo $is_archived ? 'disabled' : ''; ?>>
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
        <div class="col-12 col-md-6 mb-3">
            <label class="my-input-label">Пароль</label>
            <div class="input-group">
                <input type="text" class="form-in form-inp" id="inpPassword"
                    data-name="password" data-type="digits_only"
                    data-length="4"
                    placeholder="Сгенерируйте пароль"
                    value="" disabled>
                <button type="button" class="btn btn-generate border-start" id="btnGenPass" title="Сгенерировать пароль"
                    <?php echo $is_archived ? 'disabled' : ''; ?>>
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>

        <div class="col-12 d-flex flex-wrap gap-2">
            <?php if (!$is_archived): ?>
                <button type="submit" class="btn-action-main" id="btnSave">
                    <span id="btnSaveText">Сохранить</span>
                    <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
                </button>
                <button type="button" class="btn-danger-action" id="btnArchive">
                    Архивировать
                </button>
            <?php else: ?>
                <button type="button" class="btn-action-main" id="btnRestore">
                    Восстановить из архива
                </button>
            <?php endif; ?>
        </div>

    </div>
</form>
<script src="./_books_users/js/users_info_access.js?2026071800"></script>
