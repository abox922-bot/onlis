<?php
require_once('../app/includes/session_guard.php');
$result = fncRequireSession();
?>
<div class="section-toolbar">
    <div class="dropdown">
        <button class="btn-action-outline dropdown-toggle" type="button" id="btnStatusFilter" data-bs-toggle="dropdown" aria-expanded="false">
            Актуальные
        </button>
        <ul class="dropdown-menu" aria-labelledby="btnStatusFilter">
            <li><a class="dropdown-item" href="#" data-status="active">Актуальные</a></li>
            <li><a class="dropdown-item" href="#" data-status="all">Все</a></li>
        </ul>
    </div>
    <?php if (fncCan($result['rules'], 'products.manage')): ?>
        <button type="button" class="btn-action-main toolbar-add" id="btnFastNew">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить</span>
        </button>
    <?php endif; ?>
</div>
<div id="divChptContent"></div>
<script src="./_books_products/js/products_groups.js?2026081401"></script>
