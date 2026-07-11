<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, ['action' => 'object_utility_types_list', 'id' => $id]), 'objs');
if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-lightning-charge empty-hint__icon"></i>
        <div class="empty-hint__text">Счётчики не добавлены</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="utlTr <?php echo empty($value['is_active']) ? 'text-muted' : ''; ?>" data-id="<?php echo (int)$value['id']; ?>">
                    <td class="py-2 utlName" data-id="<?php echo (int)$value['id']; ?>">
                        <?php echo htmlspecialchars($value['name']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
