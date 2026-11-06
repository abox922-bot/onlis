<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id     = (int)($_POST['id'] ?? 0);
$status = ($_POST['status'] ?? 'active') === 'all' ? 'all' : 'active';

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_money_storages_list',
    'id'     => $id,
    'status' => $status,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}

// Группировка по типу места хранения — порядок групп задан здесь
$groups = [
    'cash_place'   => ['title' => 'Наличные',        'items' => []],
    'card'         => ['title' => 'Карты',           'items' => []],
    'bank_account' => ['title' => 'Расчётные счета', 'items' => []],
];
foreach ($result as $row) {
    if (isset($groups[$row['storage_type']])) {
        $groups[$row['storage_type']]['items'][] = $row;
    }
}
$is_empty = empty($groups['cash_place']['items'])
         && empty($groups['card']['items'])
         && empty($groups['bank_account']['items']);
?>

<?php if ($is_empty): ?>
    <div class="empty-hint">
        <i class="bi bi-safe2 empty-hint__icon"></i>
        <div class="empty-hint__text">Мест хранения денег пока нет</div>
    </div>
<?php else: ?>
    <?php foreach ($groups as $type => $group): ?>
        <?php if (empty($group['items'])) continue; ?>

        <div class="form-group-label mt-3"><?php echo $group['title']; ?></div>
        <table class="table table-sm mt-1 mb-1">
            <tbody>
                <?php foreach ($group['items'] as $value): ?>
                    <?php if ($type === 'bank_account'): ?>
                        <?php
                        $acc_tail = mb_substr($value['account_number'], -4);
                        $bank     = trim($value['bank_abbreviation'] . ' «' . $value['bank_name'] . '»');
                        ?>
                        <tr class="<?php echo !$value['is_active'] ? 'table-secondary' : ''; ?>">
                            <td class="py-2">
                                <?php echo htmlspecialchars($bank . ' · *' . $acc_tail); ?>
                                <?php if (!$value['is_active']): ?>
                                    <span class="text-muted" style="font-size: 0.8rem;"> — архив</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr class="listTr moneyStorageTr <?php echo !$value['is_active'] ? 'table-secondary' : ''; ?>"
                            data-id="<?php echo $value['id']; ?>">
                            <td class="py-2">
                                <span class="moneyStorageName" data-id="<?php echo $value['id']; ?>"><?php echo htmlspecialchars($value['name']); ?></span>
                                <?php if (!$value['is_active']): ?>
                                    <span class="text-muted" style="font-size: 0.8rem;"> — архив</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 text-end text-muted">
                                <small><?php echo htmlspecialchars($value['object_name'] ?? ''); ?></small>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($type === 'bank_account'): ?>
            <div class="text-muted mb-2" style="font-size: 0.8rem;">
                Счета добавляются на вкладке «Банковские счета»
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
