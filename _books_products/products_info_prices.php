<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'product_prices_info', 'nomenclature_id' => $id]), 'noms');
if (!is_array($data) || isset($data['sccss'])) {
    $data = ['base_price' => null, 'variations' => []];
}

$can_manage = fncCan($result['rules'], 'products.manage');
?>
<input type="hidden" id="inpProductPricesId" value="<?php echo $id; ?>">

<div class="row">
    <div class="col-12 mb-3">
        <div class="card-content-wrapper" style="padding: 14px 16px;">
            <div class="form-group-label mb-2">Базовая цена</div>
            <div class="d-flex align-items-center gap-2">
                <div class="base-price-input-wrap">
                    <input type="text"
                        id="inpBasePrice"
                        class="base-price-input"
                        data-type="digits_double"
                        value="<?php echo htmlspecialchars($data['base_price'] ?? ''); ?>"
                        placeholder="0"
                        <?php echo $can_manage ? '' : 'disabled'; ?>
                        autocomplete="off">
                    <span class="base-price-currency">₽</span>
                </div>
                <?php if ($can_manage): ?>
                    <button type="button" class="additions-toolbar__limit-step" id="btnSaveBasePrice" title="Сохранить">
                        <i class="bi bi-check-lg" id="btnSaveBasePriceIcon"></i>
                        <div class="spinner-border spinner-border-sm d-none" id="spnBasePriceLoading"></div>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($can_manage): ?>
        <div class="col-12 mb-3 d-flex justify-content-end">
            <button type="button" class="btn-action-main" id="btnAddPriceVariation">
                <i class="bi bi-plus-lg"></i>
                <span class="btn-label">Добавить</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="col-12">
        <?php if (!empty($data['variations'])): ?>
            <div class="form-group-label mb-2">Индивидуальные цены</div>
        <?php endif; ?>
        <?php if (empty($data['variations'])): ?>
            <div class="empty-hint">
                <i class="bi bi-tag empty-hint__icon"></i>
                <div class="empty-hint__text">Индивидуальные цены не заданы</div>
            </div>
        <?php else: ?>
            <?php foreach ($data['variations'] as $variation): ?>
                <div class="composition-row priceVariationRow" data-id="<?php echo (int)$variation['id']; ?>">
                    <div class="composition-row__main">
                        <span class="composition-row__name">
                            <?php if ($variation['object_type_id']): ?>
                                <?php echo htmlspecialchars($variation['object_type_name']); ?>
                                <span class="text-muted"><small>(тип объекта)</small></span>
                            <?php else: ?>
                                <?php echo htmlspecialchars($variation['object_name']); ?>
                                <span class="text-muted"><small>(объект)</small></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="fw-bold"><?php echo htmlspecialchars($variation['price']); ?> ₽</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<script src="./_books_products/js/products_info_prices.js?2026082504"></script>
