<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$user_id = (int)($_POST['user_id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action'  => 'info_person',
    'user_id' => $user_id,
]), 'users');

if (!is_array($result) || isset($result['sccss'])) {
    $result = ['countries' => []];
}

$is_archived = empty($result['actual']);
?>
<?php if ($is_archived): ?>
<div class="form-context mb-3" style="border-left-color:#dc3545;">
    Сотрудник в архиве. Доступ и учётные данные недоступны для редактирования, пока запись не восстановлена.
</div>
<?php endif; ?>

<form id="formUserPerson">
    <div class="row">

        <div class="col-12 col-md-4 mb-3">
            <label for="inpLastName" class="my-input-label">Фамилия</label>
            <input type="text" class="form-in form-inp" id="inpLastName"
                data-name="user-last" data-type="text" data-required="1"
                value="<?php echo htmlspecialchars($result['last_name'] ?? ''); ?>"
                autocomplete="off" <?php echo $is_archived ? 'disabled' : ''; ?>>
        </div>

        <div class="col-12 col-md-4 mb-3">
            <label for="inpFirstName" class="my-input-label">Имя</label>
            <input type="text" class="form-in form-inp" id="inpFirstName"
                data-name="user-name" data-type="text" data-required="1"
                value="<?php echo htmlspecialchars($result['name'] ?? ''); ?>"
                autocomplete="off" <?php echo $is_archived ? 'disabled' : ''; ?>>
        </div>

        <div class="col-12 col-md-4 mb-3">
            <label for="inpMdName" class="my-input-label">Отчество</label>
            <input type="text" class="form-in form-inp" id="inpMdName"
                data-name="user-md" data-type="text"
                value="<?php echo htmlspecialchars($result['middle_name'] ?? ''); ?>"
                autocomplete="off" <?php echo $is_archived ? 'disabled' : ''; ?>>
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpBDate" class="my-input-label">Дата рождения</label>
            <input type="date" class="form-in form-inp" id="inpBDate"
                data-name="user-bdate" data-required="1"
                value="<?php echo htmlspecialchars($result['b_date'] ?? ''); ?>"
                <?php echo $is_archived ? 'disabled' : ''; ?>>
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="slctCountry" class="my-input-label">Страна</label>
            <select id="slctCountry" <?php echo $is_archived ? 'disabled' : ''; ?>>
                <option value="">Выберите страну</option>
                <?php foreach ($result['countries'] as $c): ?>
                    <option value="<?php echo $c['id']; ?>"
                        <?php echo ($result['country_id'] ?? 0) == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpTimeZone" class="my-input-label">Часовой пояс</label>
            <input type="text" class="form-in form-inp" id="inpTimeZone"
                data-name="user-time-zone" data-type="digits_double"
                value="<?php echo htmlspecialchars($result['time_zone'] ?? ''); ?>"
                placeholder="например: 5" <?php echo $is_archived ? 'disabled' : ''; ?>>
        </div>

        <div class="col-12 mt-2">
            <div class="form-group-label">Личный телефон</div>
        </div>

        <div class="col-12 col-md-5 mb-3">
            <label for="slctPhoneCountry" class="my-input-label">Страна номера</label>
            <select id="slctPhoneCountry" <?php echo $is_archived ? 'disabled' : ''; ?>>
                <option value="">Выберите страну</option>
                <?php foreach ($result['countries'] as $c): ?>
                    <option value="<?php echo $c['id']; ?>"
                        data-code="<?php echo htmlspecialchars($c['phone_code']); ?>"
                        data-mask="<?php echo htmlspecialchars($c['phone_mask']); ?>"
                        <?php echo ($result['phone_country_id'] ?? 0) == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-7 mb-3">
            <label for="inpPhone" class="my-input-label">Телефон</label>
            <input type="text" class="form-in form-inp" id="inpPhone"
                data-name="user-phone" data-type="phone"
                data-phone-code="<?php echo htmlspecialchars($result['phone_code'] ?? ''); ?>"
                data-phone-mask="<?php echo htmlspecialchars($result['phone_mask'] ?? ''); ?>"
                value="<?php echo htmlspecialchars($result['phone'] ?? ''); ?>"
                autocomplete="off"
                <?php echo (empty($result['phone_country_id']) || $is_archived) ? 'disabled' : ''; ?>>
        </div>

        <div class="col-12 mb-3">
            <label for="inpEmail" class="my-input-label">Email</label>
            <input type="text" class="form-in form-inp" id="inpEmail"
                data-name="user-email" data-type="email"
                value="<?php echo htmlspecialchars($result['email'] ?? ''); ?>"
                autocomplete="off" <?php echo $is_archived ? 'disabled' : ''; ?>>
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>

        <?php if (!$is_archived): ?>
        <div class="col-12">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
        </div>
        <?php endif; ?>

    </div>
</form>
<script src="./_books_users/js/users_info_person.js?2026071802"></script>
