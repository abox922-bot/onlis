<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'production_items_data', 'id' => $id]), 'docs');
if (!is_array($data) || isset($data['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Не удалось загрузить строки</div></div>';
    exit;
}

$items    = $data['items'];
$is_draft = $data['status'] === 'draft';
?>
<div id="divProductionItemsInner" data-has-items="<?php echo empty($items) ? '0' : '1'; ?>" data-is-draft="<?php echo $is_draft ? '1' : '0'; ?>" data-total-sum="<?php echo $data['total_sum']; ?>">
<?php if (empty($items)): ?>
    <div class="empty-hint">
        <i class="bi bi-inbox empty-hint__icon"></i>
        <div class="empty-hint__text">ПФ ещё не добавлены</div>
    </div>
<?php else: ?>
    <?php foreach ($items as $pf): ?>
        <div class="composition-row composition-row--clickable" data-id="<?php echo (int)$pf['id']; ?>" data-production-item="1">
            <div class="composition-row__main">
                <div class="composition-row__name"><?php echo htmlspecialchars($pf['nomenclature_name']); ?></div>
                <div class="composition-row__calc text-muted">
                    <?php echo fncFormatNumber($pf['quantity']); ?> <?php echo htmlspecialchars($pf['unit_short_name']); ?>
                    <?php if ($pf['price'] !== null): ?>
                        <span class="composition-row__unit-price"><?php echo fncFormatNumber($pf['price']); ?> ₽</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($pf['sum'] !== null): ?>
                <div class="text-end me-2">
                    <div class="composition-row__sum"><?php echo fncFormatNumber($pf['sum']); ?> ₽</div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($is_draft): ?>
        <div class="ps-3 mb-2">
            <div class="form-group-label mt-2 mb-2">Ингредиенты</div>
            <?php foreach ($pf['ingredients'] as $ing): ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center text-muted">
                        <span><?php echo htmlspecialchars($ing['nomenclature_name']); ?> — <?php echo fncFormatNumber($ing['quantity']); ?> <?php echo htmlspecialchars($ing['unit_short_name']); ?></span>
                    </div>
                    <div class="form-check form-switch mt-1">
                        <input type="checkbox" class="form-check-input ingredient-auto-batch" role="switch"
                            id="chkIngredientAuto<?php echo (int)$ing['id']; ?>"
                            data-ingredient-id="<?php echo (int)$ing['id']; ?>"
                            <?php echo $ing['batch_mode'] === 'auto' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="chkIngredientAuto<?php echo (int)$ing['id']; ?>">Автоматическое распределение</label>
                    </div>
                    <div class="mt-1<?php echo $ing['batch_mode'] === 'auto' ? ' d-none' : ''; ?> ingredient-manual-block">
                        <button type="button" class="btn-action-outline btn-sm ingredient-select-batches" data-ingredient-id="<?php echo (int)$ing['id']; ?>" data-nomenclature-id="<?php echo (int)$ing['nomenclature_id']; ?>" data-quantity="<?php echo htmlspecialchars($ing['quantity']); ?>">Выбрать партии</button>
                        <span class="text-muted ms-2" style="font-size: 0.85rem;">
                            <?php echo $ing['is_distributed'] ? 'Распределено' : 'Не распределено'; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <hr>
    <?php endforeach; ?>
<?php endif; ?>
</div>
