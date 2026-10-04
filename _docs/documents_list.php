<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$date_from    = $_POST['date_from'] ?? '';
$date_to      = $_POST['date_to'] ?? '';
$type_code    = $_POST['type_code'] ?? '';
$warehouse_id = $_POST['warehouse_id'] ?? '';

$result = send_request(array_merge($ses_info, [
    'action'       => 'documents_list',
    'date_from'    => $date_from,
    'date_to'      => $date_to,
    'type_code'    => $type_code,
    'warehouse_id' => $warehouse_id,
]), 'docs');

if (!is_array($result) || isset($result['sccss'])) {
    $result = [];
}

$groups = [];
foreach ($result as $row) {
    $groups[$row['document_date']][] = $row;
}

$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

function fncDateGroupLabel($date, $today, $yesterday) {
    if ($date === $today) return 'Сегодня';
    if ($date === $yesterday) return 'Вчера';
    $months = [1=>'января',2=>'февраля',3=>'марта',4=>'апреля',5=>'мая',6=>'июня',7=>'июля',8=>'августа',9=>'сентября',10=>'октября',11=>'ноября',12=>'декабря'];
    $ts = strtotime($date);
    return (int)date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
?>
<?php if (empty($result)): ?>
    <div class="empty-hint">
        <i class="bi bi-file-earmark-text empty-hint__icon"></i>
        <div class="empty-hint__text">Документы не найдены</div>
    </div>
<?php else: ?>
    <?php foreach ($groups as $date => $items): ?>
        <div class="doc-date-group"><?php echo fncDateGroupLabel($date, $today, $yesterday); ?></div>
        <?php foreach ($items as $value): ?>
            <div class="doc-card itemTr<?php echo $value['status'] === 'posted' ? ' is-posted' : ''; ?>" data-id="<?php echo (int)$value['id']; ?>" data-type-code="<?php echo htmlspecialchars($value['code']); ?>">
                <div class="doc-card__main">
                    <div class="doc-card__title-row">
                        <div class="doc-card__title itemName" data-id="<?php echo (int)$value['id']; ?>">
                            <?php echo htmlspecialchars($value['type_name']); ?> №<?php echo htmlspecialchars($value['number']); ?>
                        </div>
                    </div>
                    <?php if ($value['counterparty_display_name']): ?>
                        <div class="doc-card__counterparty"><?php echo htmlspecialchars($value['counterparty_display_name']); ?></div>
                    <?php endif; ?>
                    <div class="doc-card__meta">
                        <?php if ($value['warehouse_in_name']): ?>
                            <span class="doc-card__meta-line">Склад поступления: <?php echo htmlspecialchars($value['warehouse_in_name']); ?></span>
                        <?php endif; ?>
                        <?php if ($value['warehouse_out_name']): ?>
                            <span class="doc-card__meta-line">Склад списания: <?php echo htmlspecialchars($value['warehouse_out_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="doc-card__sum"><?php echo fncFormatNumber($value['sum']); ?> ₽</div>
                    <?php if ((float)$value['tax_sum'] > 0): ?>
                        <div class="text-muted" style="font-size: 0.78rem;">НДС: <?php echo fncFormatNumber($value['tax_sum']); ?> ₽</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>
