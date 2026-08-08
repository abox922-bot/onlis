<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_contacts_list',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-person-lines-fill empty-hint__icon"></i>
        <div class="empty-hint__text">Контактных лиц не найдено</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="contactTr" data-id="<?php echo $value['id']; ?>">
                    <td class="py-2">
                        <span class="itemName" data-id="<?php echo $value['id']; ?>">
                            <?php echo htmlspecialchars($value['name']); ?>
                        </span>
                        <?php if (!empty($value['position'])): ?>
                            <div class="text-muted">
                                <small><?php echo htmlspecialchars($value['position']); ?></small>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($value['phone'])): ?>
                            <div class="text-muted">
                                <i class="bi bi-phone"></i>
                                <small><?php echo htmlspecialchars($value['phone']); ?></small>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($value['email'])): ?>
                            <div class="text-muted">
                                <i class="bi bi-at"></i>
                                <small><?php echo htmlspecialchars($value['email']); ?></small>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
