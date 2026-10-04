<?php
require_once('../app/includes/session_guard.php');
require_once('../modules_fncs.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$id = (int)($_POST['id'] ?? 0);

$data = send_request(array_merge($ses_info, ['action' => 'production_header_data', 'id' => $id]), 'docs');
if (!is_array($data) || isset($data['sccss'])) {
    echo '<div class="empty-hint"><div class="empty-hint__text">Документ не найден</div></div>';
    exit;
}

$info     = $data['info'];
$is_draft = $info['status'] === 'draft';
?>
<input type="hidden" id="inpDocumentId" value="<?php echo (int)$info['id']; ?>" data-warehouse-id="<?php echo (int)($info['warehouse_in_id'] ?? 0); ?>">

<div class="doc-header-compact doc-header-readonly mb-3<?php echo $is_draft ? ' doc-header-compact--clickable' : ''; ?>" id="divDocHeader">
    <?php if ($is_draft): ?>
        <i class="bi bi-pencil doc-header-compact__edit-icon"></i>
    <?php endif; ?>
    <div class="doc-header-compact__grid">
        <div class="doc-header-compact__field">
            <i class="bi bi-building doc-header-compact__icon"></i>
            <span class="doc-header-compact__label">Организация:</span>
            <span><?php echo htmlspecialchars($info['organization_display_name'] ?? ''); ?></span>
        </div>
        <div class="doc-header-compact__field">
            <i class="bi bi-box-seam doc-header-compact__icon"></i>
            <span class="doc-header-compact__label">Склад:</span>
            <span><?php echo htmlspecialchars($info['warehouse_name'] ?? ''); ?></span>
        </div>
    </div>
</div>

<?php if ($is_draft): ?>
<div class="row mb-3">
    <div class="col-12">
        <label class="my-input-label">Выберите ПФ для выпуска</label>
        <select id="slctProductionNomenclature">
            <option value="">Выберите позицию</option>
            <?php foreach ($data['nomenclature'] as $n): ?>
                <option value="<?php echo (int)$n['id']; ?>"><?php echo htmlspecialchars($n['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<hr>
<?php endif; ?>

<div id="divProductionItemsList"></div>
