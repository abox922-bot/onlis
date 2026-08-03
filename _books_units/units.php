<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
?>
<div class="section-toolbar">
    <?php if (fncCan($result['rules'], 'units.manage')): ?>
        <button type="button" class="btn-action-main toolbar-add" id="btnFastNew">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    <?php endif; ?>
</div>
<div id="divChptContent"></div>
<script src="./_books_units/js/units.js?2026080301"></script>
