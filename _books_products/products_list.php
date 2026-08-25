<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$status   = $_POST['status'] ?? 'active';
$group_id = $_POST['group_id'] ?? '';

$result = send_request(array_merge($ses_info, [
    'action'   => 'nomenclature_list',
    'section'  => 'product',
    'status'   => $status,
    'group_id' => $group_id,
]), 'noms');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-cart3 empty-hint__icon"></i>
        <div class="empty-hint__text">Товары не найдены</div>
    </div>
<?php else: ?>
    <div class="product-list">
        <?php foreach ($result as $value): ?>
            <?php $has_options = !empty($value['options_count']); ?>
            <div class="product-row-wrap" data-product-wrap="<?php echo (int)$value['id']; ?>">
              <div class="product-row itemTr<?php echo $value['is_active'] ? '' : ' tree-row-archived'; ?>" data-id="<?php echo (int)$value['id']; ?>">
                  <div class="product-row__main">
                      <span class="itemName" data-id="<?php echo (int)$value['id']; ?>">
                          <?php if (!empty($value['is_online_sale'])): ?>
                              <i class="bi bi-globe text-success" style="font-size: 0.85rem; margin-right: 4px;" title="Продажа онлайн"></i>
                          <?php endif; ?>
                          <?php echo htmlspecialchars($value['name']); ?>
                      </span>
                      <?php if ($has_options || !empty($value['group_name'])): ?>
                          <span class="product-row__meta">
                              <?php if ($has_options): ?>
                                  <span class="product-row__options-badge" title="Есть варианты">
                                      <i class="bi bi-diagram-3"></i> <?php echo (int)$value['options_count']; ?>
                                  </span>
                              <?php endif; ?>
                              <?php if (!empty($value['group_name'])): ?>
                                  <span class="product-row__group"><?php echo htmlspecialchars($value['group_name']); ?></span>
                              <?php endif; ?>
                          </span>
                      <?php endif; ?>
                  </div>
                    <?php if ($has_options): ?>
                        <button type="button" class="product-row__toggle tree-toggle" data-options-toggle="<?php echo (int)$value['id']; ?>">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <?php if ($has_options): ?>
                    <div class="product-row__options d-none" data-options-container="<?php echo (int)$value['id']; ?>" data-options-content="<?php echo (int)$value['id']; ?>"></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
