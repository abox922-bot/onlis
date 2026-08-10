<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
$can_edit = fncCan($result['rules'], 'objects.manage');
?>
<div class="row">
    <?php if ($can_edit): ?>
        <div class="col-12 mb-2 d-flex justify-content-end">
            <button type="button" class="btn-action-main toolbar-add" id="btnWorkstationNew">
                <i class="bi bi-plus-lg"></i>
                <span class="btn-label">Добавить</span>
            </button>
        </div>
    <?php endif; ?>
    <div class="col-12" id="divWorkstationsList"></div>
</div>
