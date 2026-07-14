<?php
require_once('../app/includes/session_guard.php');
$ses_result = fncRequireSession();
$today = $ses_result["today"];

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$result = send_request(array_merge($ses_info, [
    'action' => 'new_user_info',
]), 'users');

if (!is_array($result) || isset($result['sccss'])) {
    $result = ['countries' => []];
}
?>
<form id="formNew">
    <div class="row">

        <div class="col-12 col-md-4 mb-3">
            <label for="inpLastName" class="my-input-label">Фамилия</label>
            <input type="text" class="form-in form-inp" id="inpLastName"
                data-name="user-last" data-type="text" data-required="1" autocomplete="off">
        </div>

        <div class="col-12 col-md-4 mb-3">
            <label for="inpFirstName" class="my-input-label">Имя</label>
            <input type="text" class="form-in form-inp" id="inpFirstName"
                data-name="user-name" data-type="text" data-required="1" autocomplete="off">
        </div>

        <div class="col-12 col-md-4 mb-3">
            <label for="inpMdName" class="my-input-label">Отчество</label>
            <input type="text" class="form-in form-inp" id="inpMdName"
                data-name="user-md" data-type="text" autocomplete="off">
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpBDate" class="my-input-label">Дата рождения</label>
            <input type="date" class="form-in form-inp" id="inpBDate" data-name="user-bdate" data-required="1" value="<?= $today; ?>">
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="slctCountry" class="my-input-label">Страна</label>
            <select id="slctCountry">
                <option value="">Выберите страну</option>
                <?php foreach ($result['countries'] as $c): ?>
                    <option value="<?php echo $c['id']; ?>">
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mt-2">
            <div class="form-group-label">Личный телефон</div>
        </div>

        <div class="col-12 col-md-5 mb-3">
            <label for="slctPhoneCountry" class="my-input-label">Страна номера</label>
            <select id="slctPhoneCountry">
                <option value="">Выберите страну</option>
                <?php foreach ($result['countries'] as $c): ?>
                    <option value="<?php echo $c['id']; ?>"
                        data-code="<?php echo htmlspecialchars($c['phone_code']); ?>"
                        data-mask="<?php echo htmlspecialchars($c['phone_mask']); ?>">
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-7 mb-3">
            <label for="inpPhone" class="my-input-label">Телефон</label>
            <input type="text" class="form-in form-inp" id="inpPhone"
                data-name="user-phone" data-type="phone" autocomplete="off" disabled>
        </div>

        <div class="col-12 mb-3">
            <label for="inpEmail" class="my-input-label">Email</label>
            <input type="text" class="form-in form-inp" id="inpEmail"
                data-name="user-email" data-type="email" autocomplete="off">
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
