<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
?>

<div class="row">
    <div class="col-12 mb-2">
        <button type="button" class="btn-action-main" id="btnNewAcc">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить счёт</span>
        </button>
    </div>
    <div class="col-12" id="divAccsList"></div>
</div>

<script src="./_books_orgs/js/organization_info_accs.js?2026070201"></script>
