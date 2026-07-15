<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$ses_info = [
    '_onlis_id' => $_COOKIE['_onlis_id'],
    'x_token'   => $_SERVER['HTTP_X_CSRF_TOKEN'],
];

$orgs_result = send_request(array_merge($ses_info, [
    'action'   => 'organizations_list',
    'org_type' => 'all',
]), 'orgs');

if (!is_array($orgs_result) || isset($orgs_result['sccss'])) {
    $orgs_result = [];
}

$own_orgs   = array_filter($orgs_result, fn($o) => !$o['is_contractor']);
$other_orgs = array_filter($orgs_result, fn($o) => $o['is_contractor']);
?>
<div class="section-toolbar">
    <select class="toolbar-filter" id="slctOrgFilter">
        <option value="">Все сотрудники</option>
        <option value="none">Без организации</option>
        <?php if (!empty($own_orgs)): ?>
            <optgroup label="Наши организации">
                <?php foreach ($own_orgs as $o): ?>
                    <option value="<?php echo $o['id']; ?>">
                        <?php echo htmlspecialchars($o['display_name']); ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endif; ?>
        <?php if (!empty($other_orgs)): ?>
            <optgroup label="Контрагенты и банки">
                <?php foreach ($other_orgs as $o): ?>
                    <option value="<?php echo $o['id']; ?>">
                        <?php echo htmlspecialchars($o['display_name']); ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endif; ?>
    </select>
    <div class="toolbar-search">
        <i class="bi bi-search toolbar-search__icon"></i>
        <input type="text" class="form-in" id="inpSearchVal" placeholder="Поиск...">
    </div>
    <button type="button" class="btn-action-main toolbar-add" id="btnFastNew">
        <i class="bi bi-plus-lg"></i>
        <span class="btn-label">Добавить</span>
    </button>
</div>
<div id="divChptContent"></div>
<script src="./_books_users/js/users.js?2026071400"></script>
