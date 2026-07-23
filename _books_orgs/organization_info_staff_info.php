<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
$st_id    = (int)($_POST['st_id']    ?? 0);
$user_id  = (int)($_POST['user_id']  ?? 0);
?>
<input type="hidden" id="hdnStId"   value="<?php echo $st_id; ?>">
<input type="hidden" id="hdnUserId" value="<?php echo $user_id; ?>">
<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab-info active" data-target="main">Основная</button>
    <button type="button" class="inline-tab-info" data-target="person">Личная</button>
    <button type="button" class="inline-tab-info" data-target="access">Доступ</button>
    <button type="button" class="inline-tab-info" data-target="history">История</button>
</div>
<div id="divStaffInfoContent"></div>
