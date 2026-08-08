<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_contact_info',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<form id="formInfo">
    <div class="row">

        <div class="col-12 mb-3">
            <label for="inpContactName" class="my-input-label">Имя</label>
            <input type="text" class="form-in form-inp" id="inpContactName"
                data-name="contact-name" data-type="text" data-required="1"
                value="<?php echo htmlspecialchars($result['name'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="inpContactPosition" class="my-input-label">Должность</label>
            <input type="text" class="form-in form-inp" id="inpContactPosition"
                data-name="contact-position" data-type="text"
                value="<?php echo htmlspecialchars($result['position'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpContactPhone" class="my-input-label">Телефон</label>
            <input type="text" class="form-in form-inp" id="inpContactPhone"
                data-name="contact-phone" data-type="text"
                value="<?php echo htmlspecialchars($result['phone'] ?? ''); ?>"
                placeholder="+7 999 123 45 67"
                autocomplete="off">
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpContactEmail" class="my-input-label">Email</label>
            <input type="text" class="form-in form-inp" id="inpContactEmail"
                data-name="contact-email" data-type="email"
                value="<?php echo htmlspecialchars($result['email'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="inpContactNote" class="my-input-label">Примечание</label>
            <input type="text" class="form-in form-inp" id="inpContactNote"
                data-name="contact-note" data-type="text"
                value="<?php echo htmlspecialchars($result['note'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
            <button type="button" class="btn-danger-action" id="btnDelete">
                Удалить
            </button>
        </div>

    </div>
</form>
