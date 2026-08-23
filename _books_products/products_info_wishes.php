<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$items = send_request(array_merge($ses_info, ['action' => 'product_wishes_info', 'nomenclature_id' => $id]), 'noms');
if (!is_array($items) || isset($items['sccss'])) {
    $items = [];
}

$available = send_request(array_merge($ses_info, ['action' => 'product_wishes_available', 'nomenclature_id' => $id]), 'noms');
if (!is_array($available) || isset($available['sccss'])) {
    $available = [];
}

$can_manage = fncCan($result['rules'], 'products.manage');
?>
<input type="hidden" id="inpProductWishesId" value="<?php echo $id; ?>">

<div class="row">
    <?php if ($can_manage): ?>
        <div class="col-12 mb-3">
            <label for="slctWish" class="my-input-label">Добавить пожелание</label>
            <select id="slctWish">
                <option value="">Выберите пожелание</option>
                <?php foreach ($available as $wish): ?>
                    <option value="<?php echo (int)$wish['id']; ?>"><?php echo htmlspecialchars($wish['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="col-12">
        <?php if (empty($items)): ?>
            <div class="empty-hint">
                <i class="bi bi-chat-heart empty-hint__icon"></i>
                <div class="empty-hint__text">Пожелания не добавлены</div>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="composition-row" data-wish-id="<?php echo (int)$item['id']; ?>">
                    <div class="composition-row__main">
                        <span class="composition-row__name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <?php if (empty($item['is_own'])): ?>
                            <div class="text-muted">
                                <small>Унаследовано от основного товара</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($can_manage && !empty($item['is_own'])): ?>
                        <button type="button" class="btn-danger-action btnDeleteWish" data-id="<?php echo (int)$item['id']; ?>" style="padding: 4px 10px; font-size: 0.78rem;">
                            <i class="bi bi-trash"></i>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<script src="./_books_products/js/products_info_wishes.js?2026082300"></script>
