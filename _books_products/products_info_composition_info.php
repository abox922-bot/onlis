<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$info = send_request(array_merge($ses_info, ['action' => 'product_composition_item_info', 'id' => $id]), 'noms');
if (!is_array($info) || empty($info)) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Ингредиент не найден</div></div>';
    exit;
}
?>
<form id="formCompositionInfo">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="form-context"><?php echo htmlspecialchars($info['name']); ?></div>
        </div>

        <div class="col-12 mb-3">
            <label for="inpCompositionQuantity" class="my-input-label">
                Количество<?php echo !empty($info['unit_short_name']) ? ' (' . htmlspecialchars($info['unit_short_name']) . ')' : ''; ?>
            </label>
            <input type="text"
                class="form-in form-inp"
                id="inpCompositionQuantity"
                data-name="quantity"
                data-type="digits_double"
                data-required="1"
                value="<?php echo htmlspecialchars($info['quantity']); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <div class="form-group-label mb-2">Видимость</div>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="visibleRadio" id="radioVisibleYes" value="1" <?php echo ((int)$info['is_visible'] === 1) ? 'checked' : ''; ?>>
                <label class="btn" for="radioVisibleYes">Показывать</label>

                <input type="radio" class="btn-check" name="visibleRadio" id="radioVisibleNo" value="0" <?php echo ((int)$info['is_visible'] === 0) ? 'checked' : ''; ?>>
                <label class="btn" for="radioVisibleNo">Скрыть</label>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="form-group-label mb-2">Критичность</div>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="criticalRadio" id="radioCriticalYes" value="1" <?php echo ((int)$info['is_critical'] === 1) ? 'checked' : ''; ?>>
                <label class="btn" for="radioCriticalYes">Критичный</label>

                <input type="radio" class="btn-check" name="criticalRadio" id="radioCriticalNo" value="0" <?php echo ((int)$info['is_critical'] === 0) ? 'checked' : ''; ?>>
                <label class="btn" for="radioCriticalNo">Не критичный</label>
            </div>
        </div>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <div class="col-12 mt-3 d-flex gap-2">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
            <button type="button" class="btn-danger-action" id="btnDeleteCompositionItem">Удалить</button>
        </div>
    </div>
</form>
