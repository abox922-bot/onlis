<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>

<div class="row">
    <div class="col-12 mb-2">
        <button type="button" class="btn-action-main" id="btnNewPos">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить должность</span>
        </button>
    </div>
    <div class="col-12" id="divPosList"></div>
</div>

<script src="./_books_orgs/js/organization_info_positions.js?2026070301"></script>
