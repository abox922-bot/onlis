<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
?>
<div class="section-toolbar">
    <?php if (fncCan($result['rules'], 'objects.manage')): ?>
        <button type="button" class="btn-action-main toolbar-add" id="btnNewStaff">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    <?php endif; ?>
</div>
<div id="divStaffList"></div>
<script src="./_books_objs/js/object_info_staff.js?2026072501"></script>
