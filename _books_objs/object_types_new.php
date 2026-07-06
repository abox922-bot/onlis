<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];
$organizations = send_request(array_merge($ses_info, ['action' => 'organizations_list', 'org_type' => 'my']), 'orgs');
if (!is_array($organizations) || isset($organizations['sccss'])) {
    $organizations = [];
}
?>
<form id="formNew">
    <div class="row">
        <div class="col-12 col-md-6 mb-3">
            <label for="inpName" class="my-input-label">Название типа</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                autocomplete="off">
        </div>
        <div class="col-12 col-md-6 mb-3" id="rowOrganization">
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
        <?php
          if (fncCan($result["rules"], "objects")) {
            ?>
              <div class="col-12 mb-3 d-none" id="rowIsSystem">
                  <div class="form-group-label">Доступность</div>
                  <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" role="switch" id="chckIsSystem">
                      <label class="form-check-label" for="chckIsSystem">Всем организациям</label>
                  </div>
              </div>
            <?php
          }
        ?>
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
