<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$result = send_request(array_merge($ses_info, ['action' => 'units_list']), 'unt');
if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-rulers empty-hint__icon"></i>
        <div class="empty-hint__text">Единицы измерения не найдены</div>
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
                        <?php if (!empty($value['short_name'])): ?>
                            <div class="text-muted">
                                <small><?php echo htmlspecialchars($value['short_name']); ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
