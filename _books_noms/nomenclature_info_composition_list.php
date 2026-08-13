<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'composition_list', 'nomenclature_id' => $id]), 'noms');
if (!is_array($data) || isset($data['sccss'])) {
    $data = [];
}

$can_manage = fncCan($result['rules'], 'nomenclature.manage');
?>
<?php if (empty($data)): ?>
    <div class="empty-hint">
        <i class="bi bi-list-check empty-hint__icon"></i>
        <div class="empty-hint__text">Ингредиенты не добавлены</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <tbody>
            <?php foreach ($data as $item): ?>
                <tr class="<?php echo $can_manage ? 'itemCompositionTr' : ''; ?>" data-id="<?php echo (int)$item['id']; ?>">
                    <td class="py-2" style="line-height: 1.2em;">
                        <?php echo htmlspecialchars($item['name']); ?>
                        <div class="text-muted">
                            <small><?php echo $item['is_produced'] ? 'Полуфабрикат' : 'Номенклатура'; ?></small>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
