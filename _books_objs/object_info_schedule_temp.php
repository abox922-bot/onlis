<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
?>
<div class="section-toolbar">
    <?php if (fncCan($result['rules'], 'objects.manage')): ?>
        <button type="button" class="btn-action-main toolbar-add" id="btnNewTemp">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    <?php endif; ?>
</div>
<div id="divTempList"></div>
<script src="./_books_objs/js/object_info_schedule_temp.js?2026071100"></script>
