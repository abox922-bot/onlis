<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$result = send_request(array_merge($ses_info, ['action' => 'objects_list']), 'objs');
if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-shop empty-hint__icon"></i>
        <div class="empty-hint__text">Объекты не найдены</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="itemTr" data-id="<?php echo (int)$value['id']; ?>">
                    <td class="py-2 itemName" data-id="<?php echo (int)$value['id']; ?>">
                        <?php echo htmlspecialchars($value['type_name'])." ".htmlspecialchars($value['name']); ?>
                        <div class="text-muted" style="font-size: 0.8rem;">
                            <?php echo htmlspecialchars($value['org_display']); ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
