<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>

<div class="row">
    <div class="col-12 mb-2">
        <button type="button" class="btn-action-main" id="btnNewStaff">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить сотрудника</span>
        </button>
    </div>
    <div class="col-12" id="divStaffList"></div>
</div>

<script src="./_books_orgs/js/organization_info_staff.js?2026072201"></script>
