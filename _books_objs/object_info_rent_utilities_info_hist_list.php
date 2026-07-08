<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$utility_type_id = (int)($_POST['utility_type_id'] ?? 0);
$start_date       = $_POST['start_date'] ?? '';
$end_date         = $_POST['end_date'] ?? '';

$result = send_request(array_merge($ses_info, [
    'action'          => 'object_utility_readings_list',
    'utility_type_id' => $utility_type_id,
    'start_date'      => $start_date,
    'end_date'        => $end_date,
]), 'objs');
if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-clock-history empty-hint__icon"></i>
        <div class="empty-hint__text">Показаний за период нет</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Значение</th>
                <th>Тариф</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($value['reading_date']); ?></td>
                    <td><?php echo htmlspecialchars($value['reading_value']); ?></td>
                    <td><?php echo htmlspecialchars($value['tariff']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
