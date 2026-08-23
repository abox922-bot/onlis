<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$status = $_POST['status'] ?? 'active';

$result = send_request(array_merge($ses_info, ['action' => 'wishes_list', 'status' => $status]), 'noms');
if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-chat-heart empty-hint__icon"></i>
        <div class="empty-hint__text">Пожелания не найдены</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="itemTr<?php echo $value['is_active'] ? '' : ' tree-row-archived'; ?>" data-id="<?php echo (int)$value['id']; ?>">
                    <td class="py-2">
                        <span class="itemName" data-id="<?php echo (int)$value['id']; ?>">
                            <?php echo htmlspecialchars($value['name']); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
