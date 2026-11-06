<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
?>

<div class="row">
    <div class="col-12 mb-2 d-flex align-items-center gap-2">
        <?php if (fncCan($result['rules'], 'organizations.manage')): ?>
            <button type="button" class="btn-action-main" id="btnNewMoneyStorage">
                <i class="bi bi-plus-lg"></i>
                <span class="btn-label">Добавить место хранения</span>
            </button>
        <?php endif; ?>
        <div class="dropdown ms-auto">
            <button class="btn-action-outline dropdown-toggle" type="button"
                id="btnMoneyStatusFilter" data-bs-toggle="dropdown" aria-expanded="false">
                Актуальные
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="btnMoneyStatusFilter">
                <li><a class="dropdown-item moneyStatusItem" href="#" data-status="active">Актуальные</a></li>
                <li><a class="dropdown-item moneyStatusItem" href="#" data-status="all">Все</a></li>
            </ul>
        </div>
    </div>
    <div class="col-12" id="divMoneyStoragesList"></div>
</div>

<script src="./_books_orgs/js/organization_info_money.js?2026110503"></script>
