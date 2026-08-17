<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

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
$is_produced = !empty($info['is_produced']);
$is_food_product = !empty($info['is_food_product']);
$has_values = isset($info['calories']) && $info['calories'] !== null;
$can_manage = fncCan($result['rules'], 'nomenclature.manage');
?>
<input type="hidden" id="inpNutritionNomenclatureId" value="<?php echo $id; ?>">

<?php if ($is_produced): ?>

    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-context"><?php echo htmlspecialchars($basis_label); ?></div>
        </div>

        <?php if (!$has_values): ?>
            <div class="col-12">
                <div class="empty-hint">
                    <i class="bi bi-graph-up empty-hint__icon"></i>
                    <div class="empty-hint__text">Данные появятся автоматически после первого выпуска полуфабриката</div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12 mb-3">
                <label class="my-input-label">Калорийность</label>
                <div class="form-context"><?php echo htmlspecialchars($info['calories']); ?></div>
            </div>
            <div class="col-4 mb-3">
                <label class="my-input-label">Белки</label>
                <div class="form-context"><?php echo htmlspecialchars($info['proteins']); ?></div>
            </div>
            <div class="col-4 mb-3">
                <label class="my-input-label">Жиры</label>
                <div class="form-context"><?php echo htmlspecialchars($info['fats']); ?></div>
            </div>
            <div class="col-4 mb-3">
                <label class="my-input-label">Углеводы</label>
                <div class="form-context"><?php echo htmlspecialchars($info['carbohydrates']); ?></div>
            </div>
        <?php endif; ?>
    </div>

<?php elseif (!$is_food_product): ?>

    <div class="empty-hint">
        <i class="bi bi-slash-circle empty-hint__icon"></i>
        <div class="empty-hint__text">Позиция не является пищевой продукцией — КБЖУ не ведётся</div>
    </div>

<?php else: ?>

    <form id="formNutrition">
        <div class="row">
            <div class="col-12 mb-3">
                <div class="form-context"><?php echo htmlspecialchars($basis_label); ?></div>
            </div>

            <div class="col-12 mb-3">
                <label for="inpCalories" class="my-input-label">Калорийность</label>
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
                <label for="inpProteins" class="my-input-label">Белки</label>
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
                <label for="inpFats" class="my-input-label">Жиры</label>
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
                <label for="inpCarbohydrates" class="my-input-label">Углеводы</label>
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
            <?php if ($can_manage): ?>
            <div class="col-12 mt-3 d-flex gap-2">
                <button type="submit" class="btn-action-main" id="btnSave">
                    <span id="btnSaveText">Сохранить</span>
                    <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
                </button>
                <?php if ($has_values): ?>
                    <button type="button" class="btn-danger-action" id="btnClearNutrition">Очистить данные</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </form>

<?php endif; ?>

<script src="./_books_noms/js/nomenclature_info_nutrition.js?2026081600"></script>
