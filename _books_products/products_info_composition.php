<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$groups = send_request(array_merge($ses_info, ['action' => 'product_composition_list', 'nomenclature_id' => $id]), 'noms');
if (!is_array($groups) || isset($groups['sccss'])) {
    $groups = [];
}

$can_manage = fncCan($result['rules'], 'products.manage');
?>
<input type="hidden" id="inpProductCompositionId" value="<?php echo $id; ?>">

<?php if ($can_manage): ?>
    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn-action-main" id="btnAddCompositionItem">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    </div>
<?php endif; ?>

<?php if (empty($groups)): ?>
    <div class="empty-hint">
        <i class="bi bi-list-check empty-hint__icon"></i>
        <div class="empty-hint__text">Состав не заполнен</div>
    </div>
<?php else: ?>
    <div class="composition-list">
        <?php foreach ($groups as $group): ?>
            <div class="composition-group-header"><?php echo htmlspecialchars($group['title']); ?></div>
            <?php foreach ($group['items'] as $item): ?>
              <div class="composition-row listTr compositionItemTr" data-id="<?php echo (int)$item['id']; ?>" data-quantity="<?php echo htmlspecialchars($item['quantity']); ?>">
                  <div class="composition-row__main">
                      <div class="composition-row__title">
                          <span class="composition-row__name"><?php echo htmlspecialchars($item['name']); ?></span>
                          <?php if ($item['is_critical']): ?>
                              <i class="bi bi-exclamation-circle text-danger" style="font-size: 0.8rem;" title="Критичный"></i>
                          <?php else: ?>
                              <i class="bi bi-dash-circle text-muted" style="font-size: 0.8rem;" title="Не критичный"></i>
                          <?php endif; ?>
                          <?php if ($item['is_visible']): ?>
                              <i class="bi bi-eye text-success" style="font-size: 0.8rem;" title="Видимый"></i>
                          <?php else: ?>
                              <i class="bi bi-eye-slash text-muted" style="font-size: 0.8rem;" title="Скрытый от клиента"></i>
                          <?php endif; ?>
                      </div>
                      <div class="text-muted composition-row__calc">
                          <small><?php echo htmlspecialchars($item['quantity_display']); ?> <?php echo htmlspecialchars($item['unit_short_name'] ?? ''); ?> <span class="composition-row__unit-price"></span></small>
                      </div>
                  </div>
                  <div class="composition-row__sum fw-bold"></div>
              </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <div class="composition-total text-end mt-2">
            <span>Итого</span>
            <span class="fw-bold" id="compositionTotal"></span>
        </div>
    </div>
<?php endif; ?>
<script src="./_books_products/js/products_info_composition.js?2026082202"></script>
