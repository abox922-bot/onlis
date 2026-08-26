<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'nomenclature_applicability', 'nomenclature_id' => $id]), 'noms');
if (!is_array($data) || isset($data['sccss'])) {
    $data = ['unit_short_name' => null, 'semi_finished' => [], 'products' => []];
}
?>
<?php if (empty($data['semi_finished']) && empty($data['products'])): ?>
    <div class="empty-hint">
        <i class="bi bi-diagram-2 empty-hint__icon"></i>
        <div class="empty-hint__text">Позиция не используется ни в одном полуфабрикате или товаре</div>
    </div>
<?php else: ?>
    <div class="row">
        <?php if (!empty($data['semi_finished'])): ?>
            <div class="col-12 mb-3">
                <div class="form-group-label mb-2">Используется в полуфабрикатах</div>
                <?php foreach ($data['semi_finished'] as $item): ?>
                    <div class="composition-row">
                        <div class="composition-row__main">
                            <span class="composition-row__name<?php echo empty($item['is_active']) ? ' tree-row-archived' : ''; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </span>
                            <?php if (empty($item['is_active'])): ?>
                                <span class="tree-archived-label">Архив</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($data['products'])): ?>
            <div class="col-12 mb-3">
                <div class="form-group-label mb-2">Используется в товарах</div>
                <?php foreach ($data['products'] as $item): ?>
                    <div class="composition-row">
                        <div class="composition-row__main">
                            <span class="composition-row__name<?php echo empty($item['is_active']) ? ' tree-row-archived' : ''; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </span>
                            <?php if (empty($item['is_active'])): ?>
                                <span class="tree-archived-label">Архив</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted">
                            <small><?php echo htmlspecialchars($item['quantity']); ?> <?php echo htmlspecialchars($data['unit_short_name'] ?? ''); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
