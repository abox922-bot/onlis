<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$type = send_request(array_merge($ses_info, ['action' => 'object_type_info', 'id' => $id]), 'objs');
if (!is_array($type) || isset($type['sccss'])) {
    $type = [];
}
?>

<?php if (empty($type['organization_id'])): ?>

    <div class="row">
        <div class="col-12">
            <div class="form-context">
                <?php echo htmlspecialchars($type['name'] ?? ''); ?>
            </div>
        </div>
        <div class="col-12 mt-2">
            <div class="text-muted" style="font-size: 0.85rem;">
                Системный тип объектов доступен всем организациям и не может быть изменён или деактивирован.
            </div>
        </div>
    </div>

<?php else: ?>

    <form id="formInfo">
        <div class="row">
            <div class="col-12 mb-3">
                <div class="form-context">
                    <?php echo htmlspecialchars($type['org_display'] ?? ''); ?>
                </div>
            </div>

            <div class="col-12 col-md-6 mb-3">
                <label for="inpName" class="my-input-label">Название типа</label>
                <input type="text"
                    class="form-in form-inp"
                    id="inpName"
                    data-name="name"
                    data-type="text"
                    data-required="1"
                    autocomplete="off"
                    value="<?php echo htmlspecialchars($type['name'] ?? ''); ?>">
            </div>

            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input form-inp" type="checkbox" role="switch"
                        id="chckActual" data-name="is_active" data-type="check"
                        <?php echo !empty($type['is_active']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="chckActual">Активный тип</label>
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

<?php endif; ?>
