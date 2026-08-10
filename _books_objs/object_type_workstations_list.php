<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$type_id = (int)($_POST['type_id'] ?? 0);

$stations = send_request(array_merge($ses_info, ['action' => 'object_type_workstations_list', 'type_id' => $type_id]), 'objs');
if (!is_array($stations) || isset($stations['sccss'])) {
    $stations = [];
}

$can_edit = fncCan($result['rules'], 'objects.manage');
?>
<?php if (empty($stations)): ?>
    <div class="text-muted" style="font-size: 0.85rem;">Станции не привязаны</div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($stations as $st): ?>
                <tr class="<?php echo $can_edit ? 'itemWorkstationTr' : ''; ?>"
                    data-id="<?php echo (int)$st['id']; ?>"
                    <?php echo $can_edit ? 'style="cursor:pointer;"' : ''; ?>>
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
