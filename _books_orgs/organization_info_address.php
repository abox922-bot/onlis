<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id       = (int)($_POST['id']       ?? 0);
$org_type = $_POST['org_type'] ?? 'my';

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_info_address',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<input type="hidden" id="hdnOrgId"   value="<?php echo $id; ?>">
<input type="hidden" id="hdnOrgType" value="<?php echo htmlspecialchars($org_type); ?>">

<form id="formInfo">
    <div class="row">

        <div class="col-12">
            <div class="form-context">
                <?php echo htmlspecialchars($result['country_name'] ?? ''); ?>
            </div>
        </div>

        <div class="col-12 mb-3">
            <label class="my-input-label" for="slctRegion">Регион</label>
            <select class="form-in" id="slctRegion"
                data-name="adr-reg" data-type="select" data-required="1">
                <option value="0">Выберите регион</option>
                <?php foreach ($result['regions'] ?? [] as $region): ?>
                    <option value="<?php echo $region['id']; ?>"
                        <?php echo ($result['region_id'] ?? 0) == $region['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($region['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label class="my-input-label" for="slctCity">Населённый пункт</label>
            <select class="form-in" id="slctCity"
                data-name="adr-city" data-type="select" data-required="1"
                data-selected="<?php echo (int)($result['city_id'] ?? 0); ?>"
                <?php echo empty($result['region_id']) ? 'disabled' : ''; ?>>
                <option value="0">Выберите населённый пункт</option>
                <?php foreach ($result['cities'] ?? [] as $city): ?>
                    <option value="<?php echo $city['id']; ?>"
                        data-region="<?php echo $city['region']; ?>"
                        <?php echo ($result['city_id'] ?? 0) == $city['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($city['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label class="my-input-label" for="slctStreet">Улица</label>
            <select class="form-in" id="slctStreet"
                data-name="adr-str" data-type="select" data-required="1"
                data-selected="<?php echo (int)($result['street_id'] ?? 0); ?>"
                <?php echo empty($result['city_id']) ? 'disabled' : ''; ?>>
                <option value="0">Выберите улицу</option>
                <?php foreach ($result['streets'] ?? [] as $street): ?>
                    <option value="<?php echo $street['id']; ?>"
                        data-city="<?php echo $street['city']; ?>"
                        <?php echo ($result['street_id'] ?? 0) == $street['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($street['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpHouse">Дом</label>
            <input type="text"
                class="form-in form-inp"
                id="inpHouse"
                data-name="adr-house"
                data-type="text"
                data-required="1"
                value="<?php echo htmlspecialchars($result['house'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpOffice">Квартира / офис</label>
            <input type="text"
                class="form-in form-inp"
                id="inpOffice"
                data-name="adr-office"
                data-type="text"
                value="<?php echo htmlspecialchars($result['office'] ?? ''); ?>"
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

<script src="./_books_orgs/js/organization_info_address.js?2026070210"></script>
