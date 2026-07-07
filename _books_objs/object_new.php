<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$organizations = send_request(array_merge($ses_info, ['action' => 'organizations_list', 'org_type' => 'my']), 'orgs');
if (!is_array($organizations) || isset($organizations['sccss'])) {
    $organizations = [];
}

$types = send_request(array_merge($ses_info, ['action' => 'object_types_list']), 'objs');
if (!is_array($types) || isset($types['sccss'])) {
    $types = [];
}
?>
<form id="formNew">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="slctOrganization" class="my-input-label">Организация</label>
            <select class="form-in form-inp" id="slctOrganization" data-name="organization_id" data-type="select" data-required="1">
                <option value="0">Выберите организацию</option>
                <?php foreach ($organizations as $org): ?>
                    <option value="<?php echo (int)$org['id']; ?>">
                        <?php echo htmlspecialchars($org['display_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 mb-3">
            <label for="slctType" class="my-input-label">Тип объекта</label>
            <select class="form-in form-inp" id="slctType" data-name="type_id" data-type="select" data-required="1">
                <option value="0">Выберите тип</option>
                <?php foreach ($types as $type): ?>
                    <?php if (!empty($type['is_active'])): ?>
                        <option value="<?php echo (int)$type['id']; ?>">
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 mb-3">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off">
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
