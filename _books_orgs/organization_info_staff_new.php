<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'new_staff_org_info',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = ['ph_code' => '', 'ph_mask' => '', 'users' => []];
}
?>

<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab-info active" data-target="#tabNew">Новый</button>
    <button type="button" class="inline-tab-info" data-target="#tabList">Список</button>
</div>

<div id="tabNew" class="inline-tab-info-pane">
    <form id="formNew">
        <div class="row">
            <div class="col-12 col-md-4 mb-3">
                <label for="inpLastName" class="my-input-label">Фамилия</label>
                <input type="text" class="form-in form-inp" id="inpLastName"
                    data-name="staff-last" data-type="text" data-required="1" autocomplete="off">
            </div>
            <div class="col-12 col-md-4 mb-3">
                <label for="inpFirstName" class="my-input-label">Имя</label>
                <input type="text" class="form-in form-inp" id="inpFirstName"
                    data-name="staff-name" data-type="text" data-required="1" autocomplete="off">
            </div>
            <div class="col-12 col-md-4 mb-3">
                <label for="inpMdName" class="my-input-label">Отчество</label>
                <input type="text" class="form-in form-inp" id="inpMdName"
                    data-name="staff-md" data-type="text" autocomplete="off">
            </div>
            <div class="col-12 col-md-4 mb-3">
                <label for="inpBDate" class="my-input-label">Дата рождения</label>
                <input type="date" class="form-in form-inp" id="inpBDate"
                    data-name="staff-bdate">
            </div>
            <div class="col-12 col-md-4 mb-3">
                <label for="inpPhone" class="my-input-label">Телефон</label>
                <input type="text" class="form-in form-inp" id="inpPhone"
                    data-name="staff-phone" data-type="phone"
                    data-phone-code="<?php echo htmlspecialchars($result['ph_code']); ?>"
                    data-phone-mask="<?php echo htmlspecialchars($result['ph_mask']); ?>"
                    autocomplete="off">
            </div>
            <div class="col-12 col-md-4 mb-3">
                <label for="inpEmail" class="my-input-label">Email</label>
                <input type="text" class="form-in form-inp" id="inpEmail"
                    data-name="staff-email" data-type="email" autocomplete="off">
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
</div>

<div id="tabList" class="inline-tab-info-pane d-none">
    <?php if (empty($result['users'])): ?>
        <div class="empty-hint mt-3">
            <i class="bi bi-people empty-hint__icon"></i>
            <div class="empty-hint__text">Все пользователи уже в штате</div>
        </div>
    <?php else: ?>
        <table class="table table-sm table-hover mt-2">
            <tbody>
                <?php foreach ($result['users'] as $user): ?>
                    <tr class="freeTr" data-id="<?php echo $user['id']; ?>">
                        <td class="py-2"><?php echo htmlspecialchars($user['name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
