<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();

$st_id    = (int)($_POST['st_id']    ?? 0);
$org_id   = (int)($_POST['org_id']   ?? 0);
$org_type = $_POST['org_type'] ?? 'my';
?>

<input type="hidden" id="hdnStId"   value="<?php echo $st_id; ?>">
<input type="hidden" id="hdnOrgId"  value="<?php echo $org_id; ?>">
<input type="hidden" id="hdnOrgType" value="<?php echo htmlspecialchars($org_type); ?>">

<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab-info active" data-target="main">Основная</button>
    <button type="button" class="inline-tab-info" data-target="person">Личная</button>
    <button type="button" class="inline-tab-info" data-target="access">Доступ</button>
    <button type="button" class="inline-tab-info" data-target="history">История</button>
</div>

<div id="divStaffInfoContent"></div>
