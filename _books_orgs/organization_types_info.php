<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_type_info',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab active" data-target="#info">Общая</button>
    <button type="button" class="inline-tab" data-target="#reqs">Реквизиты</button>
</div>

<div id="info" class="inline-tab-pane">
    <form id="formInfo">
        <div class="row">

            <div class="col-12">
                <div class="form-context">
                    <?php echo htmlspecialchars($result['country_name'] ?? ''); ?>
                </div>
            </div>

            <div class="col-12 col-md-7">
                <label for="inpName" class="my-input-label">Название</label>
                <input type="text"
                    class="form-in form-inp"
                    id="inpName"
                    data-name="type-name"
                    data-type="text"
                    data-required="1"
                    value="<?php echo htmlspecialchars($result['name'] ?? ''); ?>">
            </div>

            <div class="col-12 col-md-5">
                <label for="inpAbbreviation" class="my-input-label">Аббревиатура</label>
                <input type="text"
                    class="form-in form-inp"
                    id="inpAbbreviation"
                    data-name="type-abbreviation"
                    data-type="text"
                    data-required="1"
                    value="<?php echo htmlspecialchars($result['abbreviation'] ?? ''); ?>">
            </div>

            <div class="col-12 mt-3">
                <div class="form-group-label">Особенности ОПФ</div>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input form-inp" type="checkbox" role="switch"
                        id="chckCanHaveBankAccount" data-name="can-have-bank-account" data-type="check"
                        <?php echo !empty($result['can_have_bank_account']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="chckCanHaveBankAccount">Может использоваться банком</label>
                </div>
            </div>

            <div class="col-12 mt-2">
                <div class="form-check form-switch">
                    <input class="form-check-input form-inp" type="checkbox" role="switch"
                        id="chckIsIndividual" data-name="is-individual" data-type="check"
                        <?php echo !empty($result['is_individual']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="chckIsIndividual">Физическое лицо</label>
                </div>
            </div>

            <div class="col-12 mt-3">
                <button type="submit" class="btn-action-main" id="btnSave">
                    <span id="btnSaveText">Сохранить</span>
                    <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
                </button>
            </div>

        </div>
    </form>
</div>

<div id="reqs" class="inline-tab-pane d-none">
    <div class="row">
        <div class="col-12 mb-2">
            <button type="button" class="btn-action-main" id="btnReqNew">
                <i class="bi bi-plus-lg"></i>
                <span class="btn-label">Добавить</span>
            </button>
        </div>
        <div class="col-12" id="divReqsList"></div>
    </div>
</div>
