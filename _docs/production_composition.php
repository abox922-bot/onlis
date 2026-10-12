<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$document_id = (int)($_POST['document_id'] ?? 0);
$nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);

$data = send_request(array_merge($ses_info, [
    'action' => 'production_composition_data',
    'document_id' => $document_id,
    'nomenclature_id' => $nomenclature_id,
]), 'docs');

if (!is_array($data) || isset($data['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Не удалось загрузить состав</div></div>';
    exit;
}

$ingredients = $data['ingredients'];
$has_blocked = false;
foreach ($ingredients as $ing) {
    if ($ing['is_blocked']) $has_blocked = true;
}
?>
<input type="hidden" id="inpProductionNomenclatureId" value="<?php echo (int)$nomenclature_id; ?>">

<?php if (empty($ingredients)): ?>
    <div class="empty-hint">
        <div class="empty-hint__text">У выбранного ПФ не задан состав</div>
    </div>
<?php else: ?>
    <div class="form-group-label mb-2">Ингредиенты</div>
    <?php foreach ($ingredients as $ing): ?>
        <div class="row mb-2 align-items-center">
            <div class="col-7">
                <div><?php echo htmlspecialchars($ing['nomenclature_name']); ?></div>
                <?php if ($ing['is_blocked']): ?>
                    <div class="text-danger" style="font-size: 0.8rem;">Нет в наличии</div>
                <?php else: ?>
                    <div class="text-muted" style="font-size: 0.8rem;">Доступно: <?php echo fncFormatQuantity($ing['available']); ?> <?php echo htmlspecialchars($ing['unit_short_name']); ?></div>
                <?php endif; ?>
            </div>
            <div class="col-5">
                <input type="text" class="form-in production-ingredient-input"
                    data-nomenclature-id="<?php echo (int)$ing['nomenclature_id']; ?>"
                    data-available="<?php echo $ing['available']; ?>"
                    <?php echo $ing['is_blocked'] ? 'disabled' : ''; ?>
                    autocomplete="off" placeholder="0">
            </div>
        </div>
    <?php endforeach; ?>

    <hr>
    <div class="mb-3">
        <label class="my-input-label">Получено ПФ</label>
        <input type="text" class="form-in" id="inpProducedQuantity" autocomplete="off">
    </div>

    <div class="col-12 mt-2 d-none" id="divProductionFormError">
        <div class="form-error-msg" id="spnProductionFormError"></div>
    </div>
    <div class="mt-3">
        <button type="button" class="btn-action-main" id="btnSaveProductionItem" <?php echo $has_blocked ? 'disabled' : ''; ?>>Сохранить</button>
        <?php if ($has_blocked): ?>
            <div class="text-danger mt-2" style="font-size: 0.85rem;">Выпуск невозможен — не хватает одного или нескольких ингредиентов</div>
        <?php endif; ?>
    </div>
<?php endif; ?>
