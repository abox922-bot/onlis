<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action' => 'organization_bank_accounts_list',
    'id'     => $id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>

<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-bank empty-hint__icon"></i>
        <div class="empty-hint__text">Счетов не найдено</div>
    </div>
<?php else: ?>
    <table class="table table-sm mt-2">
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="accTr <?php echo !$value['is_active'] ? 'table-secondary' : ''; ?>"
                    data-id="<?php echo $value['id']; ?>">
                    <td class="py-2" style="line-height: 1.4em;">

                        <div class="bankReq" data-acc="<?php echo $value['id']; ?>">
                            Расчётный счёт: <?php echo htmlspecialchars($value['account_number']); ?>
                        </div>
                        <div>
                            <small class="bankReq" data-acc="<?php echo $value['id']; ?>">
                                Банк: <?php echo htmlspecialchars($value['abbreviation'] . ' «' . $value['bank_name'] . '»'); ?>
                            </small>
                        </div>

                        <?php foreach ($value['reqs'] as $req): ?>
                            <div>
                                <small class="bankReq" data-acc="<?php echo $value['id']; ?>">
                                    <?php echo htmlspecialchars($req['name'] . ': ' . $req['value']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input accActChck" type="checkbox"
                                role="switch"
                                id="chckAcc<?php echo $value['id']; ?>"
                                data-id="<?php echo $value['id']; ?>"
                                <?php echo $value['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="chckAcc<?php echo $value['id']; ?>">
                                Действующий счёт
                            </label>
                        </div>

                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
