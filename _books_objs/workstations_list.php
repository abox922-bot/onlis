<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$result = send_request(array_merge($ses_info, ['action' => 'workstations_list']), 'objs');
if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-person-workspace empty-hint__icon"></i>
        <div class="empty-hint__text">Рабочие станции не найдены</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="itemTr" data-id="<?php echo (int)$value['id']; ?>">
                    <td class="py-2">
                        <span class="itemName" data-id="<?php echo (int)$value['id']; ?>">
                            <?php echo htmlspecialchars($value['name']); ?>
                        </span>
                        <?php if (!empty($value['has_pos_access'])): ?>
                            <div class="text-muted" style="font-size: 0.8rem;">
                                <i class="bi bi-credit-card"></i> Доступ к кассе
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
