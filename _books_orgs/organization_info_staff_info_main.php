<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$st_id = (int)($_POST['st_id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_staff_info_main',
    'st_id'  => $st_id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<form id="formStaffMain">
    <div class="row">

        <div class="col-12 col-md-6 mb-3">
            <label for="slctDep" class="my-input-label">Отдел</label>
            <select class="form-in form-inp" id="slctDep" data-name="staff-dep" data-type="select">
                <option value="0">Не указан</option>
                <?php foreach ($result['deps'] ?? [] as $dep): ?>
                    <option value="<?php echo $dep['id']; ?>"
                        <?php echo ($result['dep'] ?? 0) == $dep['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dep['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="slctTitle" class="my-input-label">Должность</label>
            <select class="form-in form-inp" id="slctTitle" data-name="staff-title" data-type="select">
                <option value="0">Не указана</option>
                <?php foreach ($result['titles'] ?? [] as $title): ?>
                    <option value="<?php echo $title['id']; ?>"
                        <?php echo ($result['title'] ?? 0) == $title['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($title['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mt-2">
            <div class="form-group-label">Рабочие контакты</div>
        </div>

        <div class="col-12 mb-3">
            <label for="inpWEmail" class="my-input-label">Email</label>
            <input type="text" class="form-in form-inp" id="inpWEmail"
                data-name="work-email" data-type="email"
                value="<?php echo htmlspecialchars($result['w_email'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-8 mb-3">
            <label for="inpWPhone" class="my-input-label">Телефон</label>
            <input type="text" class="form-in form-inp" id="inpWPhone"
                data-name="work-phone" data-type="phone"
                data-phone-code="<?php echo htmlspecialchars($result['w_code'] ?? ''); ?>"
                data-phone-mask="<?php echo htmlspecialchars($result['w_mask'] ?? ''); ?>"
                value="<?php echo htmlspecialchars($result['w_phone'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-4 mb-3">
            <label for="inpWPhoneExt" class="my-input-label">Доб.</label>
            <input type="text" class="form-in form-inp" id="inpWPhoneExt"
                data-name="work-phone-ext" data-type="digits_only"
                value="<?php echo htmlspecialchars($result['w_phone_more'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckContact" data-name="contact" data-type="check"
                    <?php echo !empty($result['contact']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckContact">Контактное лицо</label>
            </div>
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
            <button type="button" class="btn-action-outline ms-2" id="btnDismiss">
                Уволить
            </button>
        </div>

    </div>
</form>
