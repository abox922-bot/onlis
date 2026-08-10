<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<form id="formNew">
    <div class="row">
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
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input form-inp" type="checkbox" role="switch"
                    id="chckIsOperational" data-name="is_operational" data-type="check">
                <label class="form-check-label" for="chckIsOperational">Операционный объект</label>
            </div>
            <div class="text-muted mt-1" style="font-size: 0.8rem;">
                Точка, где ведётся деятельность (продажи, обслуживание), а не вспомогательный/административный объект
            </div>
        </div>
        <div class="col-12 mt-2">
            <div class="form-group-label">Доступность типа</div>
        </div>
        <div class="col-12 mb-2" id="rowOrganization">
            <label for="slctOrganization" class="my-input-label">Организация</label>
            <select class="form-in form-inp" id="slctOrganization" data-name="organization_id" data-type="select" data-required="1">
                <option value="0">Выберите организацию</option>
                <?php
                    $ses_info = [
                        '_onlis_id' => $_COOKIE['_onlis_id'],
                        'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
                    ];
                    $organizations = send_request(array_merge($ses_info, ['action' => 'organizations_list', 'org_type' => 'my']), 'orgs');
                    if (!is_array($organizations) || isset($organizations['sccss'])) {
                        $organizations = [];
                    }
                    foreach ($organizations as $org):
                ?>
                    <option value="<?php echo (int)$org['id']; ?>">
                        <?php echo htmlspecialchars($org['display_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="text-muted mt-1" style="font-size: 0.8rem;">
                Тип будет доступен только выбранной организации
            </div>
        </div>
        <div class="col-12 mb-3" id="rowIsSystem">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="chckIsSystem">
                <label class="form-check-label" for="chckIsSystem">Сделать общим</label>
            </div>
            <div class="text-muted mt-1" style="font-size: 0.8rem;">
                Тип будет доступен всем организациям, выбор конкретной организации не нужен
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
