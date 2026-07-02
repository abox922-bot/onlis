<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_info_accs',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = ['banks' => []];
}
?>

<form id="formNew">
    <div class="row">

        <div class="col-12 mb-3">
            <label for="slctBank" class="my-input-label">Банк</label>
            <select class="form-in form-inp" id="slctBank" data-name="acc-bank" data-type="select" data-required="1">
                <option value="0">Выберите банк</option>
                <?php foreach ($result['banks'] as $bank): ?>
                    <option value="<?php echo $bank['id']; ?>">
                        <?php echo htmlspecialchars($bank['abbreviation'] . ' «' . $bank['name'] . '»'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="inpAcc" class="my-input-label">Расчётный счёт</label>
            <input type="text"
                class="form-in form-inp"
                id="inpAcc"
                data-name="acc-number"
                data-type="digits_only"
                data-required="1"
                data-length="20"
                autocomplete="off"
                placeholder="20 цифр">
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
