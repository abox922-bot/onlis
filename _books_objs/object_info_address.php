<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$address = send_request(array_merge($ses_info, ['action' => 'object_info_address', 'id' => $id]), 'objs');
if (!is_array($address) || isset($address['sccss'])) {
    $address = [];
}

$countries = send_request(array_merge($ses_info, ['action' => 'countries_list']), 'geo');
if (!is_array($countries) || isset($countries['sccss'])) {
    $countries = [];
}
?>
<form id="formInfo">
    <div class="row">

        <div class="col-12 mb-3">
            <label class="my-input-label" for="slctCountry">Страна</label>
            <select class="form-in" id="slctCountry">
                <option value="">Выберите страну</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo (int)$country['id']; ?>" <?php echo ((int)($address['country_id'] ?? 0) === (int)$country['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($country['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label class="my-input-label" for="slctRegion">
                Регион
                <span class="spinner-border spinner-border-sm d-none" id="spnRegionLoading" style="width: 0.75rem; height: 0.75rem; vertical-align: middle; margin-left: 4px;"></span>
            </label>
            <select class="form-in" id="slctRegion" disabled>
                <option value="">Выберите регион</option>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label class="my-input-label" for="slctCity">
                Населённый пункт
                <span class="spinner-border spinner-border-sm d-none" id="spnCityLoading" style="width: 0.75rem; height: 0.75rem; vertical-align: middle; margin-left: 4px;"></span>
            </label>
            <select class="form-in" id="slctCity" disabled>
                <option value="">Выберите населённый пункт</option>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label class="my-input-label" for="slctStreet">
                Улица
                <span class="spinner-border spinner-border-sm d-none" id="spnStreetLoading" style="width: 0.75rem; height: 0.75rem; vertical-align: middle; margin-left: 4px;"></span>
            </label>
            <select class="form-in" id="slctStreet" disabled>
                <option value="">Выберите улицу</option>
            </select>
        </div>

        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpHouse">Дом</label>
            <input type="text"
                class="form-in form-inp"
                id="inpHouse"
                data-name="house"
                data-type="text"
                data-required="1"
                value="<?php echo htmlspecialchars($address['house'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-6 mb-3">
            <label class="my-input-label" for="inpOffice">Квартира / офис</label>
            <input type="text"
                class="form-in form-inp"
                id="inpOffice"
                data-name="office"
                data-type="text"
                value="<?php echo htmlspecialchars($address['office'] ?? ''); ?>"
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

<input type="hidden" id="hdnAddrRegionId" value="<?php echo (int)($address['region_id'] ?? 0); ?>">
<input type="hidden" id="hdnAddrCityId"   value="<?php echo (int)($address['city_id'] ?? 0); ?>">
<input type="hidden" id="hdnAddrStreetId" value="<?php echo (int)($address['street_id'] ?? 0); ?>">

<script src="./_books_objs/js/object_info_address.js?2026070809"></script>
