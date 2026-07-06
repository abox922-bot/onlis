<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$type = send_request(array_merge($ses_info, ['action' => 'object_type_info', 'id' => $id]), 'objs');
if (!is_array($type) || isset($type['sccss'])) {
    $type = [];
}

$is_system       = empty($type['organization_id']);
$can_edit_system = fncCan($result['rules'], 'objects');
?>

<?php if ($is_system && !$can_edit_system): ?>

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
                <label for="inpName" class="my-input-label">Название</label>
                <input type="text"
                    class="form-in form-inp"
                    id="inpName"
                    data-name="name"
                    data-type="text"
                    data-required="1"
                    autocomplete="off"
                    value="<?php echo htmlspecialchars($type['name'] ?? ''); ?>">
            </div>

            <?php if ($can_edit_system): ?>

                <?php
                    $organizations = send_request(array_merge($ses_info, ['action' => 'organizations_list', 'org_type' => 'my']), 'orgs');
                    if (!is_array($organizations) || isset($organizations['sccss'])) {
                        $organizations = [];
                    }
                ?>
                <div class="col-12 mb-3 <?php echo $is_system ? 'd-none' : ''; ?>" id="rowOrganization">
                    <label for="slctOrganization" class="my-input-label">Организация</label>
                    <select class="form-in <?php echo $is_system ? '' : 'form-inp'; ?>" id="slctOrganization" data-name="organization_id" data-type="select" data-required="1">
                        <option value="0">Выберите организацию</option>
                        <?php foreach ($organizations as $org): ?>
                            <option value="<?php echo (int)$org['id']; ?>" <?php echo ((int)$org['id'] === (int)($type['organization_id'] ?? 0)) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($org['display_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 mb-3" id="rowIsSystem">
                    <div class="form-group-label">Доступность</div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                            id="chckIsSystem" <?php echo $is_system ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="chckIsSystem">Всем организациям</label>
                    </div>
                </div>

            <?php else: ?>

                <div class="col-12 mb-3">
                    <div class="form-context">
                        <?php echo htmlspecialchars($type['org_display'] ?? ''); ?>
                    </div>
                </div>

            <?php endif; ?>

            <!--
            <div class="col-12 mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input form-inp" type="checkbox" role="switch"
                        id="chckActual" data-name="is_active" data-type="check"
                        <?php echo !empty($type['is_active']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="chckActual">Активный</label>
                </div>
            </div>
            -->

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
