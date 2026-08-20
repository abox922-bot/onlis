<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
require_once('../modules_fncs.php');

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$form = send_request(array_merge($ses_info, ['action' => 'nomenclature_info_form', 'id' => $id]), 'noms');
if (!is_array($form) || isset($form['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Позиция не найдена</div></div>';
    exit;
}

$info      = $form['info'];
$units     = $form['units'];
$suppliers = array_filter($form['suppliers'], fn($s) => empty($s['is_bank']));

$group_options = [];
fncFlattenGroupOptions($form['groups'], 0, [], $group_options);
?>
<form id="formInfo">
    <div class="row">

        <?php if ($info['is_produced']): ?>
            <div class="col-12 mb-3">
                <div class="form-context">Полуфабрикат</div>
            </div>
        <?php endif; ?>

        <div class="col-12 mb-3">
            <label for="inpName" class="my-input-label">Название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpName"
                data-name="name"
                data-type="text"
                data-required="1"
                value="<?php echo htmlspecialchars($info['name']); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="inpDisplayName" class="my-input-label">Видимое название</label>
            <input type="text"
                class="form-in form-inp"
                id="inpDisplayName"
                data-name="display_name"
                data-type="text"
                value="<?php echo htmlspecialchars($info['display_name'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <?php if (!$info['is_produced']): ?>
            <?php $has_nutrition_data = !empty($info['has_nutrition_data']); ?>
            <div class="col-12 mb-3">
                <div class="form-group-label mb-2">Пищевая продукция</div>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="productTypeRadio" id="radioFood" value="food" <?php echo ($info['product_type'] === 'food') ? 'checked' : ''; ?>>
                    <label class="btn" for="radioFood">Да</label>

                    <input type="radio" class="btn-check" name="productTypeRadio" id="radioNonFood" value="non_food"
                        <?php echo ($info['product_type'] === 'non_food') ? 'checked' : ''; ?>
                        <?php echo $has_nutrition_data ? 'disabled' : ''; ?>>
                    <label class="btn" for="radioNonFood">Нет</label>
                </div>
                <?php if ($has_nutrition_data): ?>
                    <div class="text-muted mt-1">
                        <small>Чтобы изменить, сначала очистите данные КБЖУ на соответствующей вкладке</small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$info['is_produced']): ?>
            <div class="col-12 col-md-6 mb-3">
                <label for="slctGroup" class="my-input-label">Группа</label>
                <select class="form-in form-inp" id="slctGroup" data-name="group_id" data-type="select" data-required="1">
                    <option value="0">Выберите группу</option>
                    <?php foreach ($group_options as $opt): ?>
                        <option value="<?php echo (int)$opt['id']; ?>" <?php echo ((int)$opt['id'] === (int)$info['group_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($opt['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-12 <?php echo $info['is_produced'] ? '' : 'col-md-6'; ?> mb-3">
            <label for="slctUnit" class="my-input-label">Единица измерения</label>
            <select class="form-in form-inp" id="slctUnit" data-name="unit_id" data-type="select" data-required="1">
                <option value="0">Выберите единицу</option>
                <?php foreach ($units as $unit): ?>
                    <option value="<?php echo (int)$unit['id']; ?>" <?php echo ((int)$unit['id'] === (int)$info['unit_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($unit['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="txtDescription" class="my-input-label">Описание</label>
            <textarea class="form-in form-inp"
                id="txtDescription"
                data-name="description"
                data-type="text"><?php echo htmlspecialchars($info['description'] ?? ''); ?></textarea>
        </div>

        <?php if (!$info['is_produced']): ?>
            <div class="col-12 mb-3">
                <label for="slctSupplier" class="my-input-label">Поставщик по умолчанию</label>
                <select class="form-in form-inp" id="slctSupplier" data-name="default_supplier_id">
                    <option value="0">Не выбран</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo (int)$supplier['id']; ?>" <?php echo ((int)$supplier['id'] === (int)$info['default_supplier_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['display_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if (!$info['is_active']): ?>
            <div class="col-12 mb-3">
                <div class="form-context">Позиция в архиве</div>
            </div>
        <?php endif; ?>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>
        <?php if (fncCan($result['rules'], 'nomenclature.manage')): ?>
        <div class="col-12 mt-3 d-flex gap-2">
            <button type="submit" class="btn-action-main" id="btnSave">
                <span id="btnSaveText">Сохранить</span>
                <div class="spinner-border spinner-border-sm d-none" id="divSaveLoading"></div>
            </button>
            <?php if ($info['is_active']): ?>
                <button type="button" class="btn-danger-action" id="btnArchive">Архивировать</button>
            <?php else: ?>
                <button type="button" class="btn-action-outline" id="btnRestore">Восстановить</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</form>
<script src="./_books_noms/js/nomenclature_info_general.js?2026081800"></script>
