<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$list = send_request(array_merge($ses_info, ['action' => 'object_types_list']), 'objs');
if (!is_array($list) || isset($list['sccss'])) {
    $list = [];
}

$can_edit_system = fncCan($result['rules'], 'objects');
?>
<?php if (empty($list)): ?>
    <div class="empty-hint">
        <i class="bi bi-tags empty-hint__icon"></i>
        <div class="empty-hint__text">Типы объектов не найдены</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($list as $value): ?>
                <?php $is_clickable = !empty($value['organization_id']) || $can_edit_system; ?>
                <tr class="<?php echo $is_clickable ? 'itemTr' : ''; ?>" data-id="<?php echo (int)$value['id']; ?>">
                    <td class="py-2 itemName" data-id="<?php echo (int)$value['id']; ?>">
                        <?php echo htmlspecialchars($value['name']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
