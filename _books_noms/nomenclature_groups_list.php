<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$status = $_POST['status'] ?? 'active';

$result = send_request(array_merge($ses_info, ['action' => 'groups_list', 'type' => 'nomenclature', 'status' => $status]), 'noms');
if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}

function fncRenderGroupsTree($nodes, $level = 0) {
    foreach ($nodes as $node) {
        $has_children = !empty($node['children']);
        ?>
        <div class="tree-node">
          <div class="tree-row itemTr<?php echo $node['is_active'] ? '' : ' tree-row-archived'; ?>" data-id="<?php echo (int)$node['id']; ?>">              <?php if ($has_children): ?>
                  <i class="bi bi-chevron-right tree-toggle"></i>
              <?php else: ?>
                  <span class="tree-toggle-placeholder"></span>
              <?php endif; ?>
              <span class="tree-name-wrap">
                  <span class="itemName" data-id="<?php echo (int)$node['id']; ?>"><?php echo htmlspecialchars($node['name']); ?></span>
                  <?php if (!$node['is_active']): ?>
                      <span class="tree-archived-label">Архив</span>
                  <?php endif; ?>
              </span>
          </div>
          <?php if ($has_children): ?>
                <div class="tree-children d-none">
                    <?php fncRenderGroupsTree($node['children'], $level + 1); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-diagram-3 empty-hint__icon"></i>
        <div class="empty-hint__text">Группы не найдены</div>
    </div>
<?php else: ?>
    <div class="tree-wrap">
        <?php fncRenderGroupsTree($result); ?>
    </div>
<?php endif; ?>
