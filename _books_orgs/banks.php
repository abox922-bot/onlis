<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>

<div class="section-toolbar">
    <div class="toolbar-search">
        <i class="bi bi-search toolbar-search__icon"></i>
        <input type="text" class="form-in" id="inpSearch" placeholder="Поиск">
    </div>
    <button type="button" class="btn-action-main toolbar-add" id="btnFastNew">
        <i class="bi bi-plus-lg"></i>
        <span class="btn-label">Добавить</span>
    </button>
</div>

<div id="divChptContent"></div>

<script src="./_books_orgs/js/organizations.js?2026070800" id="scrOrgs" data-type="bank"></script>
