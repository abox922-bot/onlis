<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$nomenclature_id = (int)($_POST['nomenclature_id'] ?? 0);

$tree = send_request(array_merge($ses_info, ['action' => 'options_tree', 'nomenclature_id' => $nomenclature_id]), 'noms');
if (!is_array($tree) || isset($tree['sccss'])) {
    $tree = [];
}

function fncRenderProductOptionsTree($nodes, $level = 0) {
    foreach ($nodes as $node) {
        $has_children = !empty($node['children']);
        $is_leaf = !empty($node['product_id']);
        ?>
        <div class="tree-node">
            <div class="tree-row" data-id="<?php echo (int)$node['id']; ?>">
                <?php if ($has_children): ?>
                    <i class="bi bi-chevron-right tree-toggle"></i>
                <?php elseif ($is_leaf): ?>
                    <i class="bi bi-box-seam text-muted tree-toggle-placeholder" style="font-size: 0.95rem; text-align: center;" title="Привязан товар"></i>
                <?php else: ?>
                    <span class="tree-toggle-placeholder"></span>
                <?php endif; ?>
                <span class="tree-name-wrap tree-name-wrap-options">
                    <?php if ($is_leaf): ?>
                        <span class="itemOptionName<?php echo empty($node['product_is_active']) ? ' tree-row-archived' : ''; ?>" data-id="<?php echo (int)$node['product_id']; ?>">
                            <?php echo htmlspecialchars($node['product_name'] ?? $node['name']); ?>
                        </span>
                    <?php else: ?>
                      <span><?php echo htmlspecialchars($node['name']); ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($has_children): ?>
                <div class="tree-children d-none">
                    <?php fncRenderProductOptionsTree($node['children'], $level + 1); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>
<?php if (empty($tree)): ?>
    <div class="empty-hint">
        <div class="empty-hint__text">Опции не добавлены</div>
    </div>
<?php else: ?>
    <?php fncRenderProductOptionsTree($tree); ?>
<?php endif; ?>
