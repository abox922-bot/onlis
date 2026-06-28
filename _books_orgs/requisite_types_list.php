<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$country_id = (int)($_POST['country_id'] ?? 0);

$result = send_request(array_merge($ses_info, [
    'action'     => 'requisite_types_list',
    'country_id' => $country_id,
]), 'orgs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}

$type_labels = [
    'text'   => 'Текст',
    'digits' => 'Цифры',
    'date'   => 'Дата',
];
?>

<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-card-list empty-hint__icon"></i>
        <div class="empty-hint__text">По выбранной стране реквизитов нет</div>
    </div>
<?php else: ?>
    <table class="table table-sm table-hover mt-2">
        <thead>
            <tr>
                <th>Название</th>
                <th>Тип значения</th>
                <th class="text-center">Длина</th>
                <th class="text-center">Уникальный</th>
                <th class="text-center">Только для банков</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($result as $value): ?>
                <tr class="itemTr" data-id="<?php echo $value['id']; ?>">
                    <td class="py-2 itemName" data-id="<?php echo $value['id']; ?>">
                        <?php echo htmlspecialchars($value['name']); ?>
                    </td>
                    <td class="py-2">
                        <?php echo $type_labels[$value['value_type']] ?? $value['value_type']; ?>
                    </td>
                    <td class="py-2 text-center">
                        <?php if ($value['has_length_control']): ?>
                            <i class="bi bi-check-lg text-success"></i>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 text-center">
                        <?php if ($value['is_unique']): ?>
                            <i class="bi bi-check-lg text-success"></i>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 text-center">
                        <?php if ($value['is_bank_only']): ?>
                            <i class="bi bi-check-lg text-success"></i>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
