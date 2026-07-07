<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$object = send_request(array_merge($ses_info, ['action' => 'object_main_info', 'id' => $id]), 'objs');
if (!is_array($object) || isset($object['sccss'])) {
    $object = [];
}

$types = send_request(array_merge($ses_info, ['action' => 'object_types_list']), 'objs');
if (!is_array($types) || isset($types['sccss'])) {
    $types = [];
}

$can_edit_org = fncCan($result['rules'], 'objects');

if ($can_edit_org) {
    $organizations = send_request(array_merge($ses_info, ['action' => 'organizations_list', 'org_type' => 'my']), 'orgs');
    if (!is_array($organizations) || isset($organizations['sccss'])) {
        $organizations = [];
    }
}
?>
<form id="formInfoMain">
    <div class="row">

        <?php if ($can_edit_org): ?>
            <div class="col-12 mb-3">
                <label for="slctOrganization" class="my-input-label">Организация</label>
                <select class="form-in form-inp" id="slctOrganization" data-name="organization_id" data-type="select" data-required="1">
                    <option value="0">Выберите организацию</option>
                    <?php foreach ($organizations as $org): ?>
                        <option value="<?php echo (int)$org['id']; ?>" <?php echo ((int)$org['id'] === (int)($object['organization_id'] ?? 0)) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($org['display_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <div class="col-12 mb-3">
                <div class="form-context">
                    <?php echo htmlspecialchars($object['org_display'] ?? ''); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="col-12 col-md-6 mb-3">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off"
                value="<?php echo htmlspecialchars($object['name'] ?? ''); ?>">
        </div>
        <div class="col-12 col-md-6 mb-3">
            <label for="slctType" class="my-input-label">Тип объекта</label>
            <select class="form-in form-inp" id="slctType" data-name="type_id" data-type="select" data-required="1">
                <option value="0">Выберите тип</option>
                <?php foreach ($types as $type): ?>
                    <?php if (!empty($type['is_active']) || (int)$type['id'] === (int)($object['type_id'] ?? 0)): ?>
                        <option value="<?php echo (int)$type['id']; ?>" <?php echo ((int)$type['id'] === (int)($object['type_id'] ?? 0)) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="inpArea" class="my-input-label">Площадь, м²</label>
            <input type="text"
                class="form-in form-inp"
                id="inpArea"
                data-name="area"
                data-type="digits_double"
                autocomplete="off"
                value="<?php echo htmlspecialchars($object['area'] ?? ''); ?>">
        </div>

        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckIsStock" data-name="is_stock" data-type="check"
                    <?php echo !empty($object['is_stock']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckIsStock">Используется как склад</label>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckActual" data-name="is_active" data-type="check"
                    <?php echo !empty($object['is_active']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckActual">Активный</label>
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
<script src="./_books_objs/js/object_info_main.js?2026070702"></script>
