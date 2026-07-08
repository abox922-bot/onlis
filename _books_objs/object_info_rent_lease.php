<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$rent = send_request(array_merge($ses_info, ['action' => 'object_info_rent', 'id' => $id]), 'objs');
if (!is_array($rent) || isset($rent['sccss'])) {
    $rent = [];
}

$is_rented = empty($rent['is_own_property']);

$owners = send_request(array_merge($ses_info, ['action' => 'organizations_list', 'org_type' => 'contractor']), 'orgs');
if (!is_array($owners) || isset($owners['sccss'])) {
    $owners = [];
}
?>
<form id="formRentLease">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="chckInRent" <?php echo $is_rented ? 'checked' : ''; ?>>
                <label class="form-check-label" for="chckInRent">Помещение в аренде</label>
            </div>
        </div>

        <div class="col-12 <?php echo $is_rented ? '' : 'd-none'; ?>" id="divRentInfo">
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="my-input-label" for="slctOwner">Арендодатель</label>
                    <select class="form-in rent-info <?php echo $is_rented ? 'form-inp' : ''; ?>"
                        data-name="owner_organization_id" data-type="select" data-required="1" id="slctOwner">
                        <option value="0">Выберите организацию</option>
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?php echo (int)$owner['id']; ?>" <?php echo ((int)($rent['owner_organization_id'] ?? 0) === (int)$owner['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($owner['display_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="my-input-label" for="inpAmount">Стоимость аренды</label>
                    <input type="text" class="form-in rent-info <?php echo $is_rented ? 'form-inp' : ''; ?>"
                        data-name="rent_amount" data-type="digits_double" data-required="1"
                        id="inpAmount" value="<?php echo htmlspecialchars($rent['rent_amount'] ?? ''); ?>" autocomplete="off">
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="my-input-label" for="inpDay">День оплаты аренды</label>
                    <input type="text" class="form-in rent-info <?php echo $is_rented ? 'form-inp' : ''; ?>"
                        data-name="rent_day_of_month" data-type="digits_only" data-required="1"
                        id="inpDay" value="<?php echo htmlspecialchars($rent['rent_day_of_month'] ?? ''); ?>" autocomplete="off">
                    <div class="text-muted mt-1" style="font-size: 0.75rem; line-height: 1.2;">
                        Если день оплаты аренды — последний день месяца, укажите «31».
                    </div>
                </div>
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
<script src="./_books_objs/js/object_info_rent_lease.js?2026070800"></script>
