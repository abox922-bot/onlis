<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);

$ingredients = send_request(array_merge($ses_info, ['action' => 'product_composition_available', 'nomenclature_id' => $nomenclature_id]), 'noms');
if (!is_array($ingredients) || isset($ingredients['sccss'])) {
    $ingredients = [];
}
?>
<form id="formCompositionNew">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="slctCompositionIngredient" class="my-input-label">Ингредиент</label>
            <select class="form-in form-inp" id="slctCompositionIngredient" data-name="ingredient_id" data-type="select" data-required="1">
                <option value="0">Выберите ингредиент</option>
                <?php foreach ($ingredients as $ingredient): ?>
                    <option value="<?php echo (int)$ingredient['id']; ?>"><?php echo htmlspecialchars($ingredient['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="inpCompositionQuantity" class="my-input-label">Количество</label>
            <input type="text"
                class="form-in form-inp"
                id="inpCompositionQuantity"
                data-name="quantity"
                data-type="digits_double"
                data-required="1"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <div class="form-group-label mb-2">Видимость</div>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="visibleRadio" id="radioVisibleYes" value="1">
                <label class="btn" for="radioVisibleYes">Показывать</label>

                <input type="radio" class="btn-check" name="visibleRadio" id="radioVisibleNo" value="0">
                <label class="btn" for="radioVisibleNo">Скрыть</label>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="form-group-label mb-2">Критичность</div>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="criticalRadio" id="radioCriticalYes" value="1">
                <label class="btn" for="radioCriticalYes">Критичный</label>

                <input type="radio" class="btn-check" name="criticalRadio" id="radioCriticalNo" value="0">
                <label class="btn" for="radioCriticalNo">Не критичный</label>
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
