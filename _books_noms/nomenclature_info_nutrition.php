<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$info = send_request(array_merge($ses_info, ['action' => 'nutrition_info', 'nomenclature_id' => $id]), 'noms');
if (!is_array($info) || isset($info['sccss'])) {
    $info = [];
}

$is_piece = !empty($info) && !$info['unit_is_float'];
$basis_label = $is_piece ? 'Пищевая ценность на 1 шт' : 'Пищевая ценность на 100 г / 100 мл';
?>
<input type="hidden" id="inpNutritionNomenclatureId" value="<?php echo $id; ?>">
<form id="formNutrition">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-context"><?php echo htmlspecialchars($basis_label); ?></div>
        </div>

        <div class="col-12 mb-3">
            <label for="inpCalories" class="my-input-label">Калорийность, ккал</label>
            <input type="text"
                class="form-in form-inp"
                id="inpCalories"
                data-name="calories"
                data-type="digits_double"
                data-required="1"
                value="<?php echo htmlspecialchars($info['calories'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-4 mb-3">
            <label for="inpProteins" class="my-input-label">Белки, г</label>
            <input type="text"
                class="form-in form-inp"
                id="inpProteins"
                data-name="proteins"
                data-type="digits_double"
                data-required="1"
                value="<?php echo htmlspecialchars($info['proteins'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-4 mb-3">
            <label for="inpFats" class="my-input-label">Жиры, г</label>
            <input type="text"
                class="form-in form-inp"
                id="inpFats"
                data-name="fats"
                data-type="digits_double"
                data-required="1"
                value="<?php echo htmlspecialchars($info['fats'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-4 mb-3">
            <label for="inpCarbohydrates" class="my-input-label">Углеводы, г</label>
            <input type="text"
                class="form-in form-inp"
                id="inpCarbohydrates"
                data-name="carbohydrates"
                data-type="digits_double"
                data-required="1"
                value="<?php echo htmlspecialchars($info['carbohydrates'] ?? ''); ?>"
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
<script src="./_books_noms/js/nomenclature_info_nutrition.js?2026080901"></script>
