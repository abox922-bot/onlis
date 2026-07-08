<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
?>
<div class="section-toolbar">
    <div></div>
    <?php if (fncCan($result['rules'], 'objects.manage')): ?>
        <button type="button" class="btn-action-main toolbar-add" id="btnNewUtility">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    <?php endif; ?>
</div>
<div id="divUtilitiesList"></div>
<script src="./_books_objs/js/object_info_rent_utilities.js?2026070800"></script>
