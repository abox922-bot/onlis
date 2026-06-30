<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$organization_type_id = (int)($_POST['organization_type_id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action'                => 'organization_type_requisites_list',
    'organization_type_id'  => $organization_type_id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-card-list empty-hint__icon"></i>
        <div class="empty-hint__text">Реквизиты для этой ОПФ не заданы</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="itemReqTr" data-id="<?php echo $value['id']; ?>">
                    <td class="py-2" style="line-height: 1.1em;">
                        <?php echo htmlspecialchars($value['name']); ?>
                        <div class="text-muted">
                            <small>
                                <?php echo $value['is_required'] ? 'Обязательный' : 'Необязательный'; ?>
                                <?php if ($value['exact_length']): ?>
                                    · <?php echo (int)$value['exact_length']; ?> симв.
                                <?php endif; ?>
                            </small>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
