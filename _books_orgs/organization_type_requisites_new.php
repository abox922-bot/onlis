<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_type_requisites_available',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<form id="formReqNew">
    <div class="row">

        <div class="col-12">
            <label for="slctRequisite" class="my-input-label">Реквизит</label>
            <select class="form-in form-req-inp" id="slctRequisite" data-name="requisite-id" data-type="select" data-required="1">
                <option value="0">Выберите</option>
                <?php foreach ($result as $value): ?>
                    <option value="<?php echo $value['id']; ?>" data-length-control="<?php echo (int)$value['has_length_control']; ?>">
                        <?php echo htmlspecialchars($value['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mt-2 d-none" id="rowExactLength">
            <label for="inpExactLength" class="my-input-label">Количество символов</label>
            <input type="text"
                class="form-in form-req-inp"
                id="inpExactLength"
                data-name="exact-length"
                data-type="digits_only">
        </div>

        <div class="col-12 mt-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-req-inp" type="checkbox" role="switch"
                    id="chckIsRequired" data-name="is-required" data-type="check" checked>
                <label class="form-check-label" for="chckIsRequired">Обязательный реквизит</label>
            </div>
        </div>

        <div class="col-12 mt-3">
            <button type="submit" class="btn-action-main" id="btnReqSave">
                <span id="btnReqSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divReqSaveLoading"></div>
            </button>
        </div>

    </div>
</form>
