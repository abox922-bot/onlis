<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'product_additions_info', 'nomenclature_id' => $id]), 'noms');
if (!is_array($data) || isset($data['sccss'])) {
    $data = ['max_quantity' => null, 'is_parent' => false, 'items' => []];
}

$can_manage = fncCan($result['rules'], 'products.manage');
?>
<input type="hidden" id="inpProductAdditionsId" value="<?php echo $id; ?>">

<div class="row">
    <?php if (empty($data['is_parent'])): ?>
        <div class="col-12 mb-3">
            <div class="card-content-wrapper" style="padding: 14px 16px;">
                <div class="form-group-label mb-2">Максимальное количество одновременных дополнений</div>
                <div class="additions-toolbar__limit">
                    <?php if ($can_manage): ?>
                        <button type="button" class="additions-toolbar__limit-step" id="btnMaxQuantityMinus">−</button>
                    <?php endif; ?>
                    <span class="additions-toolbar__limit-value" id="spnAdditionsMaxQuantity" data-value="<?php echo (int)($data['max_quantity'] ?? 0); ?>">
                        <?php echo !empty($data['max_quantity']) ? (int)$data['max_quantity'] : '∞'; ?>
                    </span>
                    <?php if ($can_manage): ?>
                        <button type="button" class="additions-toolbar__limit-step" id="btnMaxQuantityPlus">+</button>
                    <?php endif; ?>
                    <div class="spinner-border spinner-border-sm d-none" id="spnMaxQuantityLoading"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($can_manage): ?>
        <div class="col-12 mb-3 d-flex justify-content-end">
            <button type="button" class="btn-action-main" id="btnAddAddition">
                <i class="bi bi-plus-lg"></i>
                <span class="btn-label">Добавить</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="col-12">
        <?php if (!empty($data['items'])): ?>
            <div class="form-group-label mb-2">Добавленные дополнения</div>
        <?php endif; ?>
        <?php if (empty($data['items'])): ?>
            <div class="empty-hint">
                <i class="bi bi-plus-circle empty-hint__icon"></i>
                <div class="empty-hint__text">Дополнения не добавлены</div>
            </div>
        <?php else: ?>
            <?php foreach ($data['items'] as $item): ?>
                <div class="composition-row<?php echo !empty($item['is_own']) ? ' additionItemRow' : ''; ?>" data-id="<?php echo (int)$item['id']; ?>">
                    <div class="composition-row__main">
                        <span class="composition-row__name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <?php if (!empty($item['max_quantity'])): ?>
                            <span class="text-muted"><small>до <?php echo htmlspecialchars($item['max_quantity']); ?> шт.</small></span>
                        <?php endif; ?>
                        <?php if (empty($item['is_own'])): ?>
                            <div class="text-muted">
                                <small>Унаследовано от основного товара</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<script src="./_books_products/js/products_info_additions.js?2026082310"></script>
