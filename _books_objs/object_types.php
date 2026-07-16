<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>
<div class="section-toolbar">
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
<div class="empty-hint d-none" id="divEmptyHint">
    <i class="bi bi-tags empty-hint__icon"></i>
    <div class="empty-hint__text">Типы объектов не найдены</div>
</div>
<script src="./_books_objs/js/object_types.js?2026070609"></script>
