<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$countries = send_request(array_merge($ses_info, ['action' => 'countries_list']), 'geo');
if (!is_array($countries) || isset($countries['sccss'])) {
    $countries = [];
}
?>

<div class="section-toolbar">

    <select class="toolbar-filter" id="slctCountry">
        <option value="">Выберите страну</option>
        <?php foreach ($countries as $country): ?>
            <option value="<?php echo $country['id']; ?>">
                <?php echo htmlspecialchars($country['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div class="toolbar-search">
        <i class="bi bi-search toolbar-search__icon"></i>
        <input type="text" class="form-in" id="inpSearch" placeholder="Поиск">
    </div>

    <button type="button" class="btn-action-main toolbar-add" id="btnFastNew" disabled>
        <i class="bi bi-plus-lg"></i>
        <span class="btn-label">Добавить</span>
    </button>

</div>

<div id="divChptContent" class="d-none"></div>

<div class="empty-hint" id="divEmptyHint">
    <i class="bi bi-diagram-3 empty-hint__icon"></i>
    <div class="empty-hint__text">Выберите страну для просмотра ОПФ</div>
</div>

<script src="./_books_orgs/js/organization_types.js?2026070100"></script>
