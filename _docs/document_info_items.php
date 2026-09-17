<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'document_items_data', 'id' => $id]), 'docs');
if (!is_array($data) || isset($data['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Не удалось загрузить строки</div></div>';
    exit;
}

$items       = $data['items'];
$is_draft    = $data['status'] === 'draft';
$is_incoming = $data['is_incoming'];

$total_sum = 0;
$total_tax_sum = 0;
foreach ($items as $item) {
    $total_sum += (float)$item['sum'];
    $total_tax_sum += (float)$item['tax_sum'];
}
?>
<div id="divDocumentItemsInner"
     data-has-items="<?php echo empty($items) ? '0' : '1'; ?>"
     data-is-incoming="<?php echo $is_incoming ? '1' : '0'; ?>"
     data-is-draft="<?php echo $is_draft ? '1' : '0'; ?>"
     data-total-sum="<?php echo $total_sum; ?>"
     data-total-tax-sum="<?php echo $total_tax_sum; ?>">
<?php if (empty($items)): ?>
    <div class="empty-hint">
        <i class="bi bi-inbox empty-hint__icon"></i>
        <div class="empty-hint__text">Строки ещё не добавлены</div>
    </div>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <div class="composition-row composition-row--clickable" data-id="<?php echo (int)$item['id']; ?>">
            <div class="composition-row__main">
                <div class="composition-row__name"><?php echo htmlspecialchars($item['nomenclature_name']); ?></div>
                <div class="composition-row__calc text-muted">
                    <?php echo fncFormatNumber($item['quantity']); ?> <?php echo htmlspecialchars($item['unit_short_name']); ?>
                    <?php if ($item['price'] !== null): ?>
                        <span class="composition-row__unit-price"><?php echo fncFormatNumber($item['price']); ?> ₽</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($item['sum'] !== null): ?>
                <div class="text-end me-2">
                    <div class="composition-row__sum"><?php echo fncFormatNumber($item['sum']); ?> ₽</div>
                    <?php if ($is_incoming): ?>
                        <div class="text-muted" style="font-size: 0.78rem;">НДС: <?php echo fncFormatNumber($item['tax_sum']); ?> ₽</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
