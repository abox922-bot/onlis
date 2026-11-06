<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id         = (int)($_POST['id'] ?? 0);
$storage_id = (int)($_POST['storage_id'] ?? 0);

$form = send_request(array_merge($ses_info, [
    'action'     => 'organization_money_storage_form',
    'id'         => $id,
    'storage_id' => $storage_id,
]), 'orgs');

if (!is_array($form) || isset($form['sccss']) || empty($form['storage'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Место хранения не найдено</div></div>';
    exit;
}

$storage = $form['storage'];
$objects = $form['objects'] ?? [];
$is_cash = $storage['storage_type'] === 'cash_place';
?>

<form id="formInfo">
    <div class="row">

        <div class="col-12 mb-3">
            <div class="form-context">
                <?php echo $is_cash ? 'Наличные' : 'Карта'; ?>
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
                value="<?php echo htmlspecialchars($storage['name']); ?>"
                autocomplete="off">
        </div>

        <?php if ($is_cash): ?>
            <div class="col-12 mb-3">
                <label for="slctStorageObject" class="my-input-label">Объект <span class="text-muted">— необязательно</span></label>
                <select class="form-in" id="slctStorageObject">
                    <option value="0">Не привязано к объекту</option>
                    <?php foreach ($objects as $object): ?>
                        <option value="<?php echo $object['id']; ?>"
                            <?php echo (int)$object['id'] === (int)$storage['object_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($object['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckStorageActive"
                    data-name="storage-active"
                    data-type="check"
                    <?php echo !empty($storage['is_active']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckStorageActive" id="lblStorageActive">
                    <?php echo !empty($storage['is_active']) ? 'Действующее' : 'Архивное'; ?>
                </label>
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
        </div>

    </div>
</form>
