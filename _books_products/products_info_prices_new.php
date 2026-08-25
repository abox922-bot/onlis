<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);

$available = send_request(array_merge($ses_info, ['action' => 'price_variants_available', 'nomenclature_id' => $nomenclature_id]), 'noms');
if (!is_array($available) || isset($available['sccss'])) {
    $available = ['types' => [], 'objects' => []];
}
?>
<form id="formPriceNew">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-group-label mb-2">Применить к</div>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="variantTypeRadio" id="radioVariantObjectType" value="object_type">
                <label class="btn" for="radioVariantObjectType">Типу объекта</label>

                <input type="radio" class="btn-check" name="variantTypeRadio" id="radioVariantObject" value="object">
                <label class="btn" for="radioVariantObject">Объекту</label>
            </div>
        </div>

        <div class="col-12 mb-3 d-none" id="divVariantObjectType">
            <label for="slctObjectType" class="my-input-label">Тип объекта</label>
            <select class="form-in" id="slctObjectType" data-name="object_type_id" data-type="select" data-required="1">
                <option value="0">Выберите тип</option>
                <?php foreach ($available['types'] as $type): ?>
                    <option value="<?php echo (int)$type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3 d-none" id="divVariantObject">
            <label for="slctObject" class="my-input-label">Объект</label>
            <select class="form-in" id="slctObject" data-name="object_id" data-type="select" data-required="1">
                <option value="0">Выберите объект</option>
                <?php foreach ($available['objects'] as $object): ?>
                    <option value="<?php echo (int)$object['id']; ?>"><?php echo htmlspecialchars($object['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="inpVariationPrice" class="my-input-label">Цена</label>
            <input type="text"
                class="form-in form-inp"
                id="inpVariationPrice"
                data-name="price"
                data-type="digits_double"
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
