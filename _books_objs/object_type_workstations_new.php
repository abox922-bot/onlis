<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$type_id = (int)($_POST['type_id'] ?? 0);

$available = send_request(array_merge($ses_info, ['action' => 'object_type_workstations_available', 'type_id' => $type_id]), 'objs');
if (!is_array($available) || isset($available['sccss'])) {
    $available = [];
}
?>
<?php if (empty($available)): ?>
    <div class="empty-hint">
        <i class="bi bi-person-workspace empty-hint__icon"></i>
        <div class="empty-hint__text">Все станции уже привязаны</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($available as $st): ?>
                <tr class="listTr freeWorkstationTr" data-id="<?php echo (int)$st['id']; ?>">
                    <td class="py-2">
                        <?php echo htmlspecialchars($st['name']); ?>
                        <?php if (!empty($st['has_pos_access'])): ?>
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
