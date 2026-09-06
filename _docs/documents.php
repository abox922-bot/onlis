<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$toolbar_data = send_request(array_merge($ses_info, ['action' => 'documents_toolbar_data']), 'docs');
if (!is_array($toolbar_data) || isset($toolbar_data['sccss'])) {
    $toolbar_data = ['document_types' => [], 'warehouses' => []];
}
$document_types = $toolbar_data['document_types'];
$warehouses     = $toolbar_data['warehouses'];

$can_create_document = false;
foreach ($document_types as $dt) {
    if (fncCan($result['rules'], 'inventory.documents.' . $dt['code'])) {
        $can_create_document = true;
        break;
    }
}

$date_from_default = date('Y-m-d', strtotime('-7 days'));
$date_to_default    = date('Y-m-d');
?>
<div class="section-toolbar">
    <button type="button" class="btn-action-outline toolbar-filters-toggle toolbar-filters-toggle--always" id="btnToggleFilters">
        <i class="bi bi-funnel"></i>
    </button>
    <div class="toolbar-filters-group toolbar-filters-group--collapsible" id="divFiltersGroup">
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <input type="date" class="form-in" id="inpDateFrom" value="<?php echo $date_from_default; ?>">
            </div>
            <div class="col-6 col-md-3">
                <input type="date" class="form-in" id="inpDateTo" value="<?php echo $date_to_default; ?>">
            </div>
            <div class="col-6 col-md-3">
                <select class="form-in" id="slctTypeFilter">
                    <option value="">Все типы</option>
                    <?php foreach ($document_types as $dt): ?>
                        <option value="<?php echo htmlspecialchars($dt['code']); ?>"><?php echo htmlspecialchars($dt['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select class="form-in" id="slctWarehouseFilter">
                    <option value="">Все склады</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?php echo (int)$w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <?php if ($can_create_document): ?>
        <button type="button" class="btn-action-main toolbar-add" id="btnFastNew">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    <?php endif; ?>
</div>
<div id="divChptContent"></div>
<script src="./_docs/js/documents.js?2026090601"></script>
