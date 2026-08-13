<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

require_once('../modules_fncs.php');

$tree = send_request(array_merge($ses_info, ['action' => 'groups_list', 'type' => 'nomenclature', 'status' => 'active']), 'noms');
if (!is_array($tree) || isset($tree['sccss'])) {
    $tree = [];
}

$group_options = [];
fncFlattenGroupOptions($tree, 0, [], $group_options);
?>
<div class="section-toolbar">
    <button type="button" class="btn-action-outline toolbar-filters-toggle" id="btnToggleFilters">
        <i class="bi bi-funnel"></i>
    </button>

    <div class="toolbar-filters-group" id="divFiltersGroup">
        <div class="dropdown">
            <button class="btn-action-outline dropdown-toggle" type="button"
                id="btnStatusFilter" data-bs-toggle="dropdown" aria-expanded="false">
                Актуальные
            </button>
            <ul class="dropdown-menu" aria-labelledby="btnStatusFilter">
                <li><a class="dropdown-item" href="#" data-status="active">Актуальные</a></li>
                <li><a class="dropdown-item" href="#" data-status="all">Все</a></li>
            </ul>
        </div>

        <div class="toolbar-filter">
            <select class="form-in" id="slctGroupFilter">
                <option value="">Все группы</option>
                <?php foreach ($group_options as $opt): ?>
                    <option value="<?php echo (int)$opt['id']; ?>"><?php echo htmlspecialchars($opt['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="toolbar-search">
        <i class="bi bi-search toolbar-search__icon"></i>
        <input type="text" class="form-in" id="inpSearchVal" placeholder="Поиск...">
    </div>
    <?php if (fncCan($result['rules'], 'nomenclature.manage')): ?>
        <button type="button" class="btn-action-main toolbar-add" id="btnFastNew">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    <?php endif; ?>
</div>
<div id="divChptContent"></div>
<script src="./_books_noms/js/nomenclature.js?2026081300"></script>
