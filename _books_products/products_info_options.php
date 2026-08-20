<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$tree = send_request(array_merge($ses_info, ['action' => 'options_tree', 'nomenclature_id' => $id]), 'noms');
if (!is_array($tree) || isset($tree['sccss'])) {
    $tree = [];
}

$can_manage = fncCan($result['rules'], 'products.manage');

function fncRenderOptionsTree($nodes, $can_manage, $level = 0) {
    foreach ($nodes as $node) {
        $has_children = !empty($node['children']);
        $is_leaf = !empty($node['product_id']);
        ?>
        <div class="tree-node">
            <div class="tree-row" data-id="<?php echo (int)$node['id']; ?>">
                <?php if ($has_children): ?>
                    <i class="bi bi-chevron-right tree-toggle"></i>
                <?php else: ?>
                    <span class="tree-toggle-placeholder"></span>
                <?php endif; ?>
                <span class="tree-name-wrap">
                    <?php if ($is_leaf): ?>
                        <span class="itemOptionName<?php echo empty($node['product_is_active']) ? ' tree-row-archived' : ''; ?>" data-id="<?php echo (int)$node['product_id']; ?>">
                            <?php echo htmlspecialchars($node['name']); ?>
                            <i class="bi bi-box-seam text-muted" style="font-size: 0.75rem;" title="Привязан товар"></i>
                        </span>
                    <?php else: ?>
                        <span class="optionGroupName" data-id="<?php echo (int)$node['id']; ?>"><?php echo htmlspecialchars($node['name']); ?></span>
                    <?php endif; ?>
                    <?php if ($can_manage && !$is_leaf): ?>
                        <button type="button" class="btn-add-option-child" data-parent-id="<?php echo (int)$node['id']; ?>" title="Добавить дочерний элемент">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                    <?php endif; ?>
                </span>
            </div>
            <?php if ($has_children): ?>
                <div class="tree-children d-none">
                    <?php fncRenderOptionsTree($node['children'], $can_manage, $level + 1); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>
<input type="hidden" id="inpOptionsNomenclatureId" value="<?php echo $id; ?>">

<?php if ($can_manage): ?>
    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn-action-main" id="btnAddRootOption">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    </div>
<?php endif; ?>

<?php if (empty($tree)): ?>
    <div class="empty-hint">
        <i class="bi bi-diagram-3 empty-hint__icon"></i>
        <div class="empty-hint__text">Опции не добавлены</div>
    </div>
<?php else: ?>
    <div class="tree-wrap">
        <?php fncRenderOptionsTree($tree, $can_manage); ?>
    </div>
<?php endif; ?>
<script src="./_books_products/js/products_info_options.js?2026081901"></script>
