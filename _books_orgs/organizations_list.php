<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$org_type = $_POST['org_type'] ?? 'my';

$result = send_request(array_merge($ses_info, [
    'action'   => 'organizations_list',
    'org_type' => $org_type,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-building empty-hint__icon"></i>
        <div class="empty-hint__text">Организаций не найдено</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="itemTr" data-id="<?php echo $value['id']; ?>">
                    <td class="py-2 itemName" data-id="<?php echo $value['id']; ?>">
                        <?php echo htmlspecialchars($value['display_name']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
