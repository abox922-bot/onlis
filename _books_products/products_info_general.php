<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
require_once('../modules_fncs.php');

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$form = send_request(array_merge($ses_info, ['action' => 'product_info_form', 'id' => $id]), 'noms');
if (!is_array($form) || isset($form['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Товар не найден</div></div>';
    exit;
}

$info  = $form['info'];
$units = $form['units'];

$unit_is_float = 0;
foreach ($units as $u) {
    if ((int)$u['id'] === (int)$info['unit_id']) {
        $unit_is_float = (int)$u['is_float'];
        break;
    }
}

$group_options = [];
fncFlattenGroupOptions($form['groups'], 0, [], $group_options);

$can_manage = fncCan($result['rules'], 'products.manage');
?>
<input type="hidden" id="inpProductGeneralId" value="<?php echo (int)$info['id']; ?>">

<div class="card-content-wrapper mb-3" style="padding: 14px 16px;">
    <div class="form-group-label mb-2">Каналы продажи</div>
    <div class="d-flex flex-wrap gap-4">
        <div class="form-check form-switch mb-0">
            <input type="checkbox"
                class="form-check-input"
                role="switch"
                id="chkOnlineSale"
                <?php echo $info['is_online_sale'] ? 'checked' : ''; ?>
                <?php echo $can_manage ? '' : 'disabled'; ?>>
            <label class="form-check-label" for="chkOnlineSale">
                Продажа онлайн
                <span class="spinner-border spinner-border-sm d-none" id="spnOnlineSaleLoading" style="width: 0.75rem; height: 0.75rem; vertical-align: middle; margin-left: 4px;"></span>
            </label>
        </div>
        <div class="form-check form-switch mb-0">
            <input type="checkbox"
                class="form-check-input"
                role="switch"
                id="chkDeliverySale"
                <?php echo $info['is_delivery_sale'] ? 'checked' : ''; ?>
                <?php echo $can_manage ? '' : 'disabled'; ?>>
            <label class="form-check-label" for="chkDeliverySale">
                Доставка
                <span class="spinner-border spinner-border-sm d-none" id="spnDeliverySaleLoading" style="width: 0.75rem; height: 0.75rem; vertical-align: middle; margin-left: 4px;"></span>
            </label>
        </div>
    </div>
</div>

<form id="formInfo">
    <div class="row">

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

        <div class="col-12 <?php echo $unit_is_float ? '' : 'col-md-6'; ?> mb-3" id="divUnitWrap">
            <label for="slctUnit" class="my-input-label">Единица измерения</label>
            <select class="form-in form-inp" id="slctUnit" data-name="unit_id" data-type="select" data-required="1">
                <option value="0">Выберите единицу</option>
                <?php foreach ($units as $unit): ?>
                    <option value="<?php echo (int)$unit['id']; ?>"
                        data-is-float="<?php echo (int)$unit['is_float']; ?>"
                        <?php echo ((int)$unit['id'] === (int)$info['unit_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($unit['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6 mb-3 <?php echo $unit_is_float ? 'd-none' : ''; ?>" id="divOutputQuantityWrap">
            <label for="inpOutputQuantity" class="my-input-label">Выход (гр./мл.)</label>
            <input type="text"
                class="form-in form-inp"
                id="inpOutputQuantity"
                data-name="output_quantity"
                data-type="digits_double"
                value="<?php echo htmlspecialchars($info['output_quantity'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="inpBonusPercent" class="my-input-label">Бонусы, %</label>
            <input type="text"
                class="form-in form-inp"
                id="inpBonusPercent"
                data-name="bonus_percent"
                data-type="digits_double"
                value="<?php echo htmlspecialchars($info['bonus_percent'] ?? ''); ?>"
                autocomplete="off">
        </div>

        <div class="col-12 mb-3">
            <label for="txtDescription" class="my-input-label">Описание</label>
            <textarea class="form-in form-inp"
                id="txtDescription"
                data-name="description"
                data-type="text"><?php echo htmlspecialchars($info['description'] ?? ''); ?></textarea>
        </div>

        <div class="col-12 mb-3">
            <div class="form-group-label mb-2">Пищевая продукция</div>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="foodProductRadio" id="radioFoodYes" value="1" <?php echo ((int)$info['is_food_product'] === 1) ? 'checked' : ''; ?>>
                <label class="btn" for="radioFoodYes">Да</label>

                <input type="radio" class="btn-check" name="foodProductRadio" id="radioFoodNo" value="0" <?php echo ((int)$info['is_food_product'] === 0) ? 'checked' : ''; ?>>
                <label class="btn" for="radioFoodNo">Нет</label>
            </div>
        </div>

        <?php if (!$info['is_active']): ?>
            <div class="col-12 mb-3">
                <div class="form-context">Товар в архиве</div>
            </div>
        <?php endif; ?>

        <div class="col-12 mt-2 d-none" id="divFormError">
            <div class="form-error-msg" id="spnFormError"></div>
        </div>

        <?php if ($can_manage): ?>
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
<script src="./_books_products/js/products_info_general.js?2026081603"></script>
