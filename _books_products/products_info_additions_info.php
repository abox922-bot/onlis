<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$info = send_request(array_merge($ses_info, ['action' => 'addition_info', 'id' => $id]), 'noms');
if (!is_array($info) || empty($info)) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Дополнение не найдено</div></div>';
    exit;
}
?>
<form id="formAdditionInfo">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-context"><?php echo htmlspecialchars($info['name']); ?></div>
        </div>

        <div class="col-12 mb-3">
            <label for="inpAdditionMaxQuantity" class="my-input-label">Максимальное количество</label>
            <input type="text"
                class="form-in form-inp"
                id="inpAdditionMaxQuantity"
                data-name="max_quantity"
                data-type="digits_double"
                placeholder="Без ограничения"
                value="<?php echo htmlspecialchars($info['max_quantity'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <div class="col-12 mt-3 d-flex gap-2">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
            <button type="button" class="btn-danger-action" id="btnDeleteAddition">Удалить</button>
        </div>
    </div>
</form>
