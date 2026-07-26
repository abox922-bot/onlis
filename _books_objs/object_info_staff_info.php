<?php
require_once('../app/includes/session_guard.php');
fncRequireSession();
$staff_id = (int)($_POST['staff_id'] ?? 0);
$user_id  = (int)($_POST['user_id']  ?? 0);
?>
<input type="hidden" id="hdnStaffId" value="<?php echo $staff_id; ?>">
<input type="hidden" id="hdnStaffUserId" value="<?php echo $user_id; ?>">
<div class="inline-tabs mb-3">
    <button type="button" class="inline-tab-info active" data-target="main">Основная</button>
    <button type="button" class="inline-tab-info" data-target="schedule">График</button>
    <button type="button" class="inline-tab-info" data-target="person">Личная</button>
</div>
<div id="divStaffInfoContent"></div>
