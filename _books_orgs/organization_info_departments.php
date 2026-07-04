<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$id       = (int)($_POST['id']       ?? 0);
$org_type = $_POST['org_type'] ?? 'my';
?>

<input type="hidden" id="hdnOrgId"   value="<?php echo $id; ?>">
<input type="hidden" id="hdnOrgType" value="<?php echo htmlspecialchars($org_type); ?>">

<div class="row">
    <div class="col-12 mb-2">
        <button type="button" class="btn-action-main" id="btnNewDep">
            <i class="bi bi-plus-lg"></i>
            <span class="btn-label">Добавить отдел</span>
        </button>
    </div>
    <div class="col-12" id="divDepsList"></div>
</div>

<script src="./_books_orgs/js/organization_info_departments.js?2026070301"></script>
