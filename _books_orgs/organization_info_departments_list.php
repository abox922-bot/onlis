<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_departments_list',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-diagram-3 empty-hint__icon"></i>
        <div class="empty-hint__text">Отделов не найдено</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="depTr <?php echo !$value['is_active'] ? 'table-secondary' : ''; ?>"
                    data-id="<?php echo $value['id']; ?>">
                    <td class="py-2 itemName" data-id="<?php echo $value['id']; ?>">
                        <?php echo htmlspecialchars($value['name']); ?>
                        <?php if (!$value['is_active']): ?>
                            <span class="text-muted" style="font-size: 0.8rem;"> — архив</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
