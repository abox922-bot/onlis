<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$organization_id = $_POST['organization_id'] ?? '';

$result = send_request(array_merge($ses_info, [
    'action'          => 'users_list',
    'organization_id' => $organization_id,
]), 'users');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-people empty-hint__icon"></i>
        <div class="empty-hint__text">Записей не найдено</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $u): ?>
                <tr class="itemTr" data-id="<?php echo $u['user_id']; ?>">
                    <td class="py-2 itemName" data-id="<?php echo $u['user_id']; ?>"
                        style="line-height: 1.2em;">
                        <?php echo htmlspecialchars($u['full_name']); ?>
                        <?php if (!empty($u['orgs_display'])): ?>
                            <div class="text-muted">
                                <small><?php echo htmlspecialchars($u['orgs_display']); ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
