<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$info = send_request(array_merge($ses_info, ['action' => 'package_info', 'id' => $id]), 'noms');
if (!is_array($info) || empty($info)) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Упаковка не найдена</div></div>';
    exit;
}
?>
<input type="hidden" id="inpPackageId" value="<?php echo (int)$info['id']; ?>">
<form id="formPackageInfo">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="inpPackageName" class="my-input-label">Название упаковки</label>
            <input type="text"
                class="form-in form-inp"
                id="inpPackageName"
                data-name="name"
                data-type="text"
                data-required="1"
                value="<?php echo htmlspecialchars($info['name']); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="inpPackageQuantity" class="my-input-label">
                Количество<?php echo !empty($info['unit_short_name']) ? ' (' . htmlspecialchars($info['unit_short_name']) . ')' : ''; ?>
            </label>
            <input type="text"
                class="form-in form-inp"
                id="inpPackageQuantity"
                data-name="quantity"
                data-type="digits_double"
                data-required="1"
                value="<?php echo htmlspecialchars($info['quantity']); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input type="checkbox"
                    class="form-check-input form-inp"
                    role="switch"
                    id="chkPackageDefault"
                    data-name="is_default"
                    data-type="check"
                    <?php echo $info['is_default'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chkPackageDefault">Упаковка по умолчанию</label>
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
