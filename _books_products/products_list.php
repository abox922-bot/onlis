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
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <?php $has_options = !empty($value['options_count']); ?>
                <tr class="itemTr<?php echo $value['is_active'] ? '' : ' tree-row-archived'; ?>" data-id="<?php echo (int)$value['id']; ?>">
                    <td class="py-2 d-flex align-items-center">
                        <?php if ($has_options): ?>
                            <i class="bi bi-chevron-right tree-toggle" data-options-toggle="<?php echo (int)$value['id']; ?>"></i>
                        <?php else: ?>
                            <span class="tree-toggle-placeholder"></span>
                        <?php endif; ?>
                        <span class="tree-name-wrap tree-name-wrap-options">
                            <span>
                                <?php if (!empty($value['is_online_sale'])): ?>
                                    <i class="bi bi-globe text-success" style="font-size: 0.85rem; margin-right: 4px;" title="Продажа онлайн"></i>
                                <?php endif; ?>
                                <span class="itemName" data-id="<?php echo (int)$value['id']; ?>">
                                    <?php echo htmlspecialchars($value['name']); ?>
                                </span>
                                <?php if (!empty($value['group_name'])): ?>
                                    <div class="text-muted">
                                        <small><?php echo htmlspecialchars($value['group_name']); ?></small>
                                    </div>
                                <?php endif; ?>
                            </span>
                        </span>
                    </td>
                </tr>
                <?php if ($has_options): ?>
                    <tr class="d-none tree-options-container" data-options-container="<?php echo (int)$value['id']; ?>">
                        <td class="p-0">
                            <div class="tree-children ms-4" data-options-content="<?php echo (int)$value['id']; ?>"></div>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
