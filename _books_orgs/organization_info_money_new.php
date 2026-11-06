<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

// Один объединяющий action для формы: и создание (storage_id = 0), и редактирование
$form = send_request(array_merge($ses_info, [
    'action'     => 'organization_money_storage_form',
    'id'         => $id,
    'storage_id' => 0,
]), 'orgs');

if (!is_array($form) || isset($form['sccss'])) {
    $form = ['objects' => []];
}
$objects = $form['objects'] ?? [];
?>

<form id="formNew">
    <div class="row">

        <div class="col-12 mb-3">
            <label class="my-input-label">Тип</label>
            <div class="d-flex gap-2">
                <input type="radio" class="btn-check" name="rdoStorageType" id="rdoTypeCash" value="cash_place" checked>
                <label class="btn-action-outline" for="rdoTypeCash">
                    <i class="bi bi-cash-stack"></i>Наличные
                </label>
                <input type="radio" class="btn-check" name="rdoStorageType" id="rdoTypeCard" value="card">
                <label class="btn-action-outline" for="rdoTypeCard">
                    <i class="bi bi-credit-card"></i>Карта
                </label>
            </div>
        </div>

        <div class="col-12 mb-3">
            <label for="inpStorageName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpStorageName"
                data-name="storage-name"
                data-type="text"
                data-required="1"
                autocomplete="off">
            <div class="text-muted mt-1" style="font-size: 0.8rem;" id="divStorageNameHint">
                Например «Сейф офиса», «У владельца»
            </div>
        </div>

        <div class="col-12 mb-3" id="divStorageObject">
            <label for="slctStorageObject" class="my-input-label">Объект <span class="text-muted">— необязательно</span></label>
            <select class="form-in" id="slctStorageObject">
                <option value="0">Не привязано к объекту</option>
                <?php foreach ($objects as $object): ?>
                    <option value="<?php echo $object['id']; ?>">
                        <?php echo htmlspecialchars($object['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
